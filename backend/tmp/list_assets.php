<?php
require __DIR__ . "/../vendor/autoload.php";
$app = require __DIR__ . "/../bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class);
$rows = Illuminate\Support\Facades\DB::select("
  select table_schema, table_name
  from information_schema.tables
  where table_type = 'BASE TABLE'
    and table_schema in ( 'public', 'assets' )
    and table_name ilike 'asset%' 
  order by table_schema, table_name
");
foreach ($rows as $r) {
  echo $r->table_schema . "." . $r->table_name . PHP_EOL;
}
