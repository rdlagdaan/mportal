<?php
require __DIR__."/vendor/autoload.php";
$app = require __DIR__."/bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use Illuminate\Support\Facades\DB;

// convert boolean -> text
DB::statement("ALTER TABLE assets.assets
  ALTER COLUMN loan_agreement TYPE text USING loan_agreement::text");

// set a default you expect from the UI
DB::statement("ALTER TABLE assets.assets
  ALTER COLUMN loan_agreement SET DEFAULT 'Default'");

// normalize any NULL/empty rows
DB::statement("UPDATE assets.assets
  SET loan_agreement = COALESCE(NULLIF(loan_agreement, ), 'Default')
  WHERE loan_agreement IS NULL OR loan_agreement = ");

// show result
\$r = DB::select(\"select column_name, data_type
                  from information_schema.columns
                  where table_schema=assets
                    and table_name=assets
                    and column_name=loan_agreement\");
print_r(\$r);
