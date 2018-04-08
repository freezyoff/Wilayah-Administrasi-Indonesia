# Administrative Teritory division of Indonesia (Provinces, Cities, Districts, & Villages)
The data are latest update from <a href="http://mfdonline.bps.go.id/" target="_blank">Central Agency on Statistics (Badan Pusat Statistic) - MFD and MBS Update</a>.

## How to use
- Create & Install Laravel Project [https://laravel.com/docs/5.6#installation],
- Copy the file to <code>Laravel</code> Installation directories,
- Open <code>database/migration/*create_area_codes_table</code> file and modify:
<pre>
	// modify to your own connection name, see config/app.php
	protected $connection = "";			--> modify to your connection name
	
	// modify to your own table name
	protected $table = "area_codes";	--> modify to your table name
</pre>
- Run <code>Laravel migration</code> to create the <code>table</code>
- Finally, run <code>Laravel artisan command</code>:<br>
<pre>
	php artisan freezy:fetch_mfdonline
</pre>

## Command Options
* <code><strong>--tableClass</strong></code> - PHP Namespace of your table
* <code><strong>--fresh</strong></code> - Truncate (delete all data) from table before running the scripts

## License
* The scripts are license under: [MIT](license.md).
* The source data is attributed to <a href="" target="_blank">**Badan Pusat Statistik (BPS) Indonesia**</a>.

## Contributing
Your contributions are **Great Help** for others, come:
1. Fork it (https://github.com/freezyoff/Wilayah-Administrasi-Indonesia).
2. Create your feature branch (`git checkout -b my-new-feature`).
3. Commit your changes (`git commit -am 'Add some feature'`).
4. Push to the branch (`git push origin my-new-feature`).
5. Create a new Pull Request.
