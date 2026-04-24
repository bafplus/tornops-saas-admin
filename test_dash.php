<?php
require __DIR__."/vendor/autoload.php";
$app = require_once __DIR__."/bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$settings = App\Models\FactionSettings::first();
$twoWeeksAgo = now()->subDays(14)->timestamp;

$ocParticipation = DB::table("organized_crime_slots")
    ->select("user_id", DB::raw("MAX(user_joined_at) as last_oc"))
    ->whereNotNull("user_id")
    ->groupBy("user_id");

$inactiveMembers = App\Models\FactionMember::where("faction_id", $settings->faction_id ?? 0)
    ->leftJoinSub($ocParticipation, "ocs", function($join) {
        $join->on("faction_members.player_id", "=", "ocs.user_id");
    })
    ->where(function($query) use ($twoWeeksAgo) {
        $query->where("last_oc", "<", $twoWeeksAgo)
              ->orWhereNull("last_oc");
    })
    ->select("faction_members.player_id", "faction_members.name", "last_oc")
    ->orderBy("last_oc")
    ->limit(20)
    ->get();

echo "Found " . count($inactiveMembers) . " inactive members\n";
foreach ($inactiveMembers->take(3) as $m) {
    echo $m->name . " - last_oc: " . ($m->last_oc ?? "null") . "\n";
}
