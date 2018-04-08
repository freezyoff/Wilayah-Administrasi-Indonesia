<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\JNCore\AreaCodeModel;

class FetchAreaCodesTableCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fetch:fetch_mfdonline 
							{--tableClass=\App\Models\JNCore\AreaCodeModel : Eloquent Table Class namespace to store, default: \App\Models\JNCore\AreaCodeModel}
							{--fresh : Truncate table first before running}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetching data from "http://mfdonline.bps.go.id/index.php?link=hasil_pencarian';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

	protected $startTime = null;
	protected $remoteUrl = "http://mfdonline.bps.go.id/index.php?link=hasil_pencarian";
	protected $loopKeys = ['a','i','u','e','o'];
	protected $tableClass = null;
	
	protected function handleOptionFresh(){
		//truncate database table first
		if ($this->option('fresh')){
			$this->writeComment("Prepare database: ", false);
			$this->tableClass::truncate();
			$this->writeComment("truncated ", true, false);
		}
	}
	
	protected function handleOptionTable(){
		$class = $this->option('tableClass');
		if ($class){
			$this->tableClass = $class;
		}
	}
	
	protected function handleRegex($str){
		$pattern = ['/\s+/', '/(\d+)\s{1,}(\d+)/', '/(\d+)\s{1,}(\D+)/', '/(\D+)\s{1,}(\d+)/'];
		$replace = [" ","$1,$2","$1,$2","$1,$2"];
		return preg_replace($pattern, $replace, trim($str));
	}
	
    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(){
		
		$this->startTime = microtime(true);
		
		$this->handleOptionTable();
		$this->handleOptionFresh();
		
		$content = [];
		$iterationCount = 0;
		foreach($this->loopKeys as $key){
			$this->writeInfo("Reading Remote data: key='".$key."'", false);
			//$this->loop($key);
			$tags = $this->getContents($key);
			$content[$key] = $tags->childNodes;
			$iterationCount+= count($content[$key])-1;
			$this->writeInfo(" complete", true, false);
		}
		
		//storing to database, start iteration
		foreach($content as $tag) { $this->loop($tag, $iterationCount); }
		
		$this->writeInfo("Fetching complete!!!");
    }
	
	function loop($table, $iterationTotal){
		$iterationCount = 0;
		foreach($table as $tr){
			if ($tr->getAttribute('class') === 'table_header') {
				continue;
			}
			
			//clean char - NON UTF8
			$text = $this->handleRegex($tr->nodeValue);
			$expl = explode(",",$text);
			
			//print iteration
			$this->writeInfo("Iteration ".($iterationCount++)." of ".$iterationTotal." : ", false);
			
			//provinsi
			$province = $this->getProvince($expl);
			$obj = $this->tableClass::find($province['code']);
			if (!is_object($obj)){
				$this->writeComment("Save Provinsi= ".$province['name'], false, false);
				$this->tableClass::updateOrCreate($province);
				$this->writeComment(" | ", false, false);
			}
			
			//kabupaten & kota
			$district = $this->getDistrict($expl);
			$obj = $this->tableClass::find($district['code']);
			if (!is_object($obj)){
				$this->writeComment("Save Kabupaten/Kota= ".$district['name'], false, false);
				$this->tableClass::updateOrCreate($district);
				$this->writeComment(" | ", false, false);
			}
			
			//Kecamatan
			$subdistrict = $this->getSubdistrict($expl);
			$obj = $this->tableClass::find($subdistrict['code']);
			if (!is_object($obj)){
				$this->writeComment("Save Kecamatan: ".$subdistrict['name'], false, false);
				$this->tableClass::updateOrCreate($subdistrict);
				$this->writeComment(" | ", false, false);
			}
			
			//Kelurahan & Desa
			$village = $this->getVillage($expl);
			$obj = $this->tableClass::find($village['code']);
			if (!is_object($obj)){
				$this->writeComment("Save Desa / Kelurahan: ".$village['name'], false, false);
				$this->tableClass::updateOrCreate($village);
				$this->writeComment(" | ", false, false);
			}
			$this->writeComment("", true, false);
		}
	}
	
	function getContents($key){
		$postdata = http_build_query(
			array(
				'pilihcari' => 'desa',
				'kata_kunci' => $key
			)
		);

		$opts = array('http' =>
			array(
				'method'  => 'POST',
				'header'  => 'Content-type: application/x-www-form-urlencoded',
				'content' => $postdata
			)
		);

		$context  = stream_context_create($opts);

		$result = file_get_contents($this->remoteUrl, false, $context);
		
		//replace char non UTF8
		$result = preg_replace('/[^[:print:]]/', '', $result);
		
		//load HTML
		$doc = new \DOMDocument();
		$doc->preserveWhiteSpace = true;
		@$doc->loadHTML($result);
		$tags = $doc->getElementsByTagName("table");
		return $tags[1];
	}
	
	function writeInfo($msg, $endLine=true, $withElapse=true){
		$msg = $withElapse? $this->elapse()." ".$msg : $msg;
		if ($endLine)	$this->output->writeln("<info>$msg</info>");
		else 			$this->output->write("<info>$msg</info>");
	}
	
	function writeComment($msg, $endLine=true, $withElapse=true){
		$msg = $withElapse? $this->elapse()." ".$msg : $msg;
		if ($endLine)	$this->output->writeln("<comment>$msg</comment>");
		else 			$this->output->write("<comment>$msg</comment>");
	}
	
	function elapse(){
		$endtime = microtime(true);
		$s = $endtime - $this->startTime;
		$h = floor($s / 3600);
		$s -= $h * 3600;
		$m = floor($s / 60);
		$s -= $m * 60;
		
		$cs = ceil($s);
		return ($h>=10?$h:'0'.$h).":".($m>=10?$m:'0'.$m).":". ($cs>=60? '00': ($cs>=10? $cs : '0'.$cs));
	}
	
	function getProvince($array){
		return [
			'code'=>			str_pad($array[1], 10, "0"),
			'name'=>			$array[2],
			'parent_code'=>		str_pad("", 10, "0")
		];
	}
	
	function getDistrict($array){
		return [
			'code'=>			str_pad($array[1].$array[3], 10, "0"),
			'name'=>			$array[4],
			'parent_code'=>		str_pad($array[1], 10, "0")
		];
	}
	
	function getSubdistrict($array){
		return [
			'code'=>			str_pad($array[1].$array[3].$array[5], 10, "0"),
			'name'=>			$array[6],
			'parent_code'=>		str_pad($array[1].$array[3],10,"0")
		];
	}
	
	function getVillage($array){
		$name = "";
		for($i=8; $i<count($array); $i++) {
			$name.=  ($name===""? "" : " "). $array[$i];
		}
		return [
			'code'=>			str_pad($array[1].$array[3].$array[5].$array[7], 10, "0"),
			'name'=>			$name,
			'parent_code'=>		str_pad($array[1].$array[3].$array[5],10,"0")
		];
	}
}