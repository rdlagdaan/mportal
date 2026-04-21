<?php
declare(strict_types=1);

require __DIR__ . "/../vendor/autoload.php";
$app    = require __DIR__ . "/../bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    // Ensure schemas exist (no-op if they already do)
    DB::statement("CREATE SCHEMA IF NOT EXISTS public");
    DB::statement("CREATE SCHEMA IF NOT EXISTS assets");

    // Verify base table
    $base = DB::selectOne("SELECT to_regclass(assets.assets) AS t");
    if (!$base || !$base->t) {
        throw new RuntimeException("Base table assets.assets not found");
    }

    // 1) public.asset_details -> all columns from assets.assets
    DB::statement("CREATE OR REPLACE VIEW public.asset_details AS SELECT a.* FROM assets.assets a");

    // 2) public.asset_detail_search -> lowercased fields used by controller
    $cols = DB::select("
      SELECT column_name
        FROM information_schema.columns
       WHERE table_schema = assets AND table_name = assets
    ");
    $have = array_map(fn($r) => $r->column_name, $cols);
    $has  = fn(string $c) => in_array($c, $have, true);

    $assetId = $has("id")            ? "a.id"                                   : ($has("asset_id") ? "a.asset_id" : "NULL::bigint");
    $assetNo = $has("asset_no")      ? "lower(coalesce(a.asset_no,))"         : "::text";
    $desc    = $has("description")   ? "lower(coalesce(a.description,))"      : "::text";
    $ref     = $has("reference")     ? "lower(coalesce(a.reference,))"        : "::text";
    $supp    = $has("supplier_name") ? "lower(coalesce(a.supplier_name,))"    : "::text";
    $serial  = $has("serial_no")     ? "lower(coalesce(a.serial_no,))"        : "::text";

    $sql = <<<SQL
CREATE OR REPLACE VIEW public.asset_detail_search AS
SELECT
  {$assetId} AS asset_id,
  {$assetNo} AS asset_no_l,
  {$desc}    AS description_l,
  {$ref}     AS reference_l,
  {$supp}    AS supplier_name_l,
  {$serial}  AS serial_no_l
FROM assets.assets a
SQL;

    DB::statement($sql);
    echo "OK: created/updated public.asset_details and public.asset_detail_search\n";
} catch (Throwable $e) {
    fwrite(STDERR, "ERROR: ".$e->getMessage()."\n");
    exit(1);
}
