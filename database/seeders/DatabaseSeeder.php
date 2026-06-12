<?php

namespace Database\Seeders;

use App\Models\Fixture;
use App\Models\ScoringRule;
use App\Models\Stadium;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // The admin account is the only seeded user — real employees register
        // themselves and make their own predictions.
        User::create([
            'name' => 'Muslim Youth League Admin',
            'email' => 'admin@iocod.com',
            'is_admin' => true,
            'referral_code' => 'ADMIN001',
            // The model's "hashed" cast hashes this automatically.
            'password' => bcrypt('Admin@fifa123'),
            'email_verified_at' => now(),
        ]);

        $tournament = Tournament::create([
            'name' => 'FIFA World Cup 2026',
            'slug' => 'fifa-world-cup-2026',
            'year' => 2026,
            'start_date' => '2026-06-11',
            'end_date' => '2026-07-19',
            'is_active' => true,
        ]);

        ScoringRule::create([
            'tournament_id' => $tournament->id,
            'correct_winner_points' => 5,
            'correct_draw_points' => 3,
            'exact_score_points' => 10,
            'correct_goal_difference_points' => 2,
            'correct_one_team_score_points' => 1,
            'knockout_multiplier' => 2,
        ]);

        $teams = $this->seedTeams();
        $stadiums = $this->seedStadiums();

        // Real group-stage schedule (date/venue/UTC). Results are entered by an
        // admin once matches are played.
        $this->seedFixtures($tournament, $teams, $stadiums);

        // Knockout bracket: real schedule (date/venue/UTC), teams left as TBD until
        // group results are known. Admins fill the slots from the admin panel.
        $this->seedKnockoutFixtures($tournament, $stadiums);

        // A sample home-page advertisement so the banner slot is populated.
        $this->call(AdvertisementSeeder::class);
    }

    /**
     * Create the 48 qualified nations across the 12 groups (December 2025 draw).
     *
     * @return array<string, Team> keyed by 3-letter FIFA code
     */
    private function seedTeams(): array
    {
        $groups = [
            'A' => [['Mexico', 'MEX'], ['South Africa', 'RSA'], ['South Korea', 'KOR'], ['Czechia', 'CZE']],
            'B' => [['Canada', 'CAN'], ['Bosnia and Herzegovina', 'BIH'], ['Qatar', 'QAT'], ['Switzerland', 'SUI']],
            'C' => [['Brazil', 'BRA'], ['Morocco', 'MAR'], ['Haiti', 'HAI'], ['Scotland', 'SCO']],
            'D' => [['United States', 'USA'], ['Paraguay', 'PAR'], ['Australia', 'AUS'], ['Türkiye', 'TUR']],
            'E' => [['Germany', 'GER'], ['Curaçao', 'CUW'], ['Ivory Coast', 'CIV'], ['Ecuador', 'ECU']],
            'F' => [['Netherlands', 'NED'], ['Japan', 'JPN'], ['Sweden', 'SWE'], ['Tunisia', 'TUN']],
            'G' => [['Belgium', 'BEL'], ['Egypt', 'EGY'], ['Iran', 'IRN'], ['New Zealand', 'NZL']],
            'H' => [['Spain', 'ESP'], ['Cape Verde', 'CPV'], ['Saudi Arabia', 'KSA'], ['Uruguay', 'URU']],
            'I' => [['France', 'FRA'], ['Senegal', 'SEN'], ['Iraq', 'IRQ'], ['Norway', 'NOR']],
            'J' => [['Argentina', 'ARG'], ['Algeria', 'ALG'], ['Austria', 'AUT'], ['Jordan', 'JOR']],
            'K' => [['Portugal', 'POR'], ['DR Congo', 'COD'], ['Uzbekistan', 'UZB'], ['Colombia', 'COL']],
            'L' => [['England', 'ENG'], ['Croatia', 'CRO'], ['Ghana', 'GHA'], ['Panama', 'PAN']],
        ];

        $teams = [];

        $flagCodes = [
            'MEX' => 'mx', 'RSA' => 'za', 'KOR' => 'kr', 'CZE' => 'cz',
            'CAN' => 'ca', 'BIH' => 'ba', 'QAT' => 'qa', 'SUI' => 'ch',
            'BRA' => 'br', 'MAR' => 'ma', 'HAI' => 'ht', 'SCO' => 'gb-sct',
            'USA' => 'us', 'PAR' => 'py', 'AUS' => 'au', 'TUR' => 'tr',
            'GER' => 'de', 'CUW' => 'cw', 'CIV' => 'ci', 'ECU' => 'ec',
            'NED' => 'nl', 'JPN' => 'jp', 'SWE' => 'se', 'TUN' => 'tn',
            'BEL' => 'be', 'EGY' => 'eg', 'IRN' => 'ir', 'NZL' => 'nz',
            'ESP' => 'es', 'CPV' => 'cv', 'KSA' => 'sa', 'URU' => 'uy',
            'FRA' => 'fr', 'SEN' => 'sn', 'IRQ' => 'iq', 'NOR' => 'no',
            'ARG' => 'ar', 'ALG' => 'dz', 'AUT' => 'at', 'JOR' => 'jo',
            'POR' => 'pt', 'COD' => 'cd', 'UZB' => 'uz', 'COL' => 'co',
            'ENG' => 'gb-eng', 'CRO' => 'hr', 'GHA' => 'gh', 'PAN' => 'pa',
        ];

        foreach ($groups as $group => $members) {
            foreach ($members as $position => [$name, $code]) {
                $teams[$code] = Team::create([
                    'name' => $name,
                    'slug' => Str::slug($name),
                    'country_code' => $code,
                    'flag' => isset($flagCodes[$code]) ? "https://flagcdn.com/w320/{$flagCodes[$code]}.png" : null,
                    'group' => $group,
                    'group_position' => $position + 1,
                ]);
            }
        }

        return $teams;
    }

    /**
     * Create the 16 host venues.
     *
     * @return array<string, Stadium> keyed by venue name
     */
    private function seedStadiums(): array
    {
        $venues = [
            ['Estadio Azteca', 'Mexico City', 'Mexico', 'MEX', 87523],
            ['Estadio Akron', 'Guadalajara', 'Mexico', 'MEX', 49850],
            ['Estadio BBVA', 'Monterrey', 'Mexico', 'MEX', 53500],
            ['BMO Field', 'Toronto', 'Canada', 'CAN', 45000],
            ['BC Place', 'Vancouver', 'Canada', 'CAN', 54500],
            ['SoFi Stadium', 'Los Angeles', 'United States', 'USA', 70240],
            ["Levi's Stadium", 'Santa Clara', 'United States', 'USA', 68500],
            ['MetLife Stadium', 'East Rutherford', 'United States', 'USA', 82500],
            ['Gillette Stadium', 'Foxborough', 'United States', 'USA', 65878],
            ['NRG Stadium', 'Houston', 'United States', 'USA', 72220],
            ['AT&T Stadium', 'Arlington', 'United States', 'USA', 80000],
            ['Lincoln Financial Field', 'Philadelphia', 'United States', 'USA', 69596],
            ['Mercedes-Benz Stadium', 'Atlanta', 'United States', 'USA', 71000],
            ['Lumen Field', 'Seattle', 'United States', 'USA', 68740],
            ['Hard Rock Stadium', 'Miami Gardens', 'United States', 'USA', 65326],
            ['Arrowhead Stadium', 'Kansas City', 'United States', 'USA', 76416],
        ];

        $stadiums = [];

        foreach ($venues as [$name, $city, $country, $countryCode, $capacity]) {
            $stadiums[$name] = Stadium::create([
                'name' => $name,
                'city' => $city,
                'country' => $country,
                'country_code' => $countryCode,
                'capacity' => $capacity,
            ]);
        }

        return $stadiums;
    }

    /**
     * Seed all 72 group-stage fixtures with kickoff times in UTC. Results are left
     * empty — an admin enters them as matches are played.
     *
     * @param  array<string, Team>  $teams
     * @param  array<string, Stadium>  $stadiums
     */
    private function seedFixtures(Tournament $tournament, array $teams, array $stadiums): void
    {
        foreach ($this->groupStageSchedule() as $match) {
            $kickoff = Carbon::parse($match['utc']);

            Fixture::create([
                'tournament_id' => $tournament->id,
                'home_team_id' => $teams[$match['home']]->id,
                'away_team_id' => $teams[$match['away']]->id,
                'stadium_id' => $stadiums[$match['venue']]->id,
                'round' => 'group_stage',
                'group' => $match['group'],
                'match_day' => intdiv($match['match'] - 1, 24) + 1,
                'scheduled_at' => $kickoff,
                'predictions_locked_at' => $kickoff,
                'status' => 'upcoming',
                'points_calculated' => false,
            ]);
        }
    }

    /**
     * Seed the 32 knockout fixtures (Round of 32 through the Final) with the real
     * schedule. Both teams point at a shared "TBD" placeholder until results are in.
     *
     * @param  array<string, Stadium>  $stadiums
     */
    private function seedKnockoutFixtures(Tournament $tournament, array $stadiums): void
    {
        $tbd = Team::create([
            'name' => 'TBD',
            'slug' => 'tbd',
            'country_code' => 'TBD',
            'group' => null,
            'group_position' => null,
        ]);

        foreach ($this->knockoutSchedule() as $match) {
            Fixture::create([
                'tournament_id' => $tournament->id,
                'home_team_id' => $tbd->id,
                'away_team_id' => $tbd->id,
                'stadium_id' => $stadiums[$match['venue']]->id,
                'round' => $match['round'],
                'group' => null,
                'match_day' => null,
                'scheduled_at' => Carbon::parse($match['utc']),
                'predictions_locked_at' => Carbon::parse($match['utc']),
                'status' => 'upcoming',
                'points_calculated' => false,
            ]);
        }
    }

    /**
     * The official 2026 FIFA World Cup knockout schedule (matches 73–104) with
     * kickoff times in UTC. Teams are determined by group results, so they are
     * seeded as TBD and filled in later from the admin panel.
     *
     * @return list<array{match:int, round:string, utc:string, venue:string}>
     */
    private function knockoutSchedule(): array
    {
        return [
            ['match' => 73, 'round' => 'round_of_32', 'utc' => '2026-06-28T19:00:00Z', 'venue' => 'SoFi Stadium'],
            ['match' => 74, 'round' => 'round_of_32', 'utc' => '2026-06-29T20:30:00Z', 'venue' => 'Gillette Stadium'],
            ['match' => 75, 'round' => 'round_of_32', 'utc' => '2026-06-30T01:00:00Z', 'venue' => 'Estadio BBVA'],
            ['match' => 76, 'round' => 'round_of_32', 'utc' => '2026-06-29T17:00:00Z', 'venue' => 'NRG Stadium'],
            ['match' => 77, 'round' => 'round_of_32', 'utc' => '2026-06-30T21:00:00Z', 'venue' => 'MetLife Stadium'],
            ['match' => 78, 'round' => 'round_of_32', 'utc' => '2026-06-30T17:00:00Z', 'venue' => 'AT&T Stadium'],
            ['match' => 79, 'round' => 'round_of_32', 'utc' => '2026-07-01T01:00:00Z', 'venue' => 'Estadio Azteca'],
            ['match' => 80, 'round' => 'round_of_32', 'utc' => '2026-07-01T16:00:00Z', 'venue' => 'Mercedes-Benz Stadium'],
            ['match' => 81, 'round' => 'round_of_32', 'utc' => '2026-07-02T00:00:00Z', 'venue' => "Levi's Stadium"],
            ['match' => 82, 'round' => 'round_of_32', 'utc' => '2026-07-01T20:00:00Z', 'venue' => 'Lumen Field'],
            ['match' => 83, 'round' => 'round_of_32', 'utc' => '2026-07-02T23:00:00Z', 'venue' => 'BMO Field'],
            ['match' => 84, 'round' => 'round_of_32', 'utc' => '2026-07-02T19:00:00Z', 'venue' => 'SoFi Stadium'],
            ['match' => 85, 'round' => 'round_of_32', 'utc' => '2026-07-03T03:00:00Z', 'venue' => 'BC Place'],
            ['match' => 86, 'round' => 'round_of_32', 'utc' => '2026-07-03T22:00:00Z', 'venue' => 'Hard Rock Stadium'],
            ['match' => 87, 'round' => 'round_of_32', 'utc' => '2026-07-04T01:30:00Z', 'venue' => 'Arrowhead Stadium'],
            ['match' => 88, 'round' => 'round_of_32', 'utc' => '2026-07-03T18:00:00Z', 'venue' => 'AT&T Stadium'],
            ['match' => 89, 'round' => 'round_of_16', 'utc' => '2026-07-04T21:00:00Z', 'venue' => 'Lincoln Financial Field'],
            ['match' => 90, 'round' => 'round_of_16', 'utc' => '2026-07-04T17:00:00Z', 'venue' => 'NRG Stadium'],
            ['match' => 91, 'round' => 'round_of_16', 'utc' => '2026-07-05T20:00:00Z', 'venue' => 'MetLife Stadium'],
            ['match' => 92, 'round' => 'round_of_16', 'utc' => '2026-07-06T00:00:00Z', 'venue' => 'Estadio Azteca'],
            ['match' => 93, 'round' => 'round_of_16', 'utc' => '2026-07-06T19:00:00Z', 'venue' => 'AT&T Stadium'],
            ['match' => 94, 'round' => 'round_of_16', 'utc' => '2026-07-07T00:00:00Z', 'venue' => 'Lumen Field'],
            ['match' => 95, 'round' => 'round_of_16', 'utc' => '2026-07-07T16:00:00Z', 'venue' => 'Mercedes-Benz Stadium'],
            ['match' => 96, 'round' => 'round_of_16', 'utc' => '2026-07-07T20:00:00Z', 'venue' => 'BC Place'],
            ['match' => 97, 'round' => 'quarter_final', 'utc' => '2026-07-09T20:00:00Z', 'venue' => 'Gillette Stadium'],
            ['match' => 98, 'round' => 'quarter_final', 'utc' => '2026-07-10T19:00:00Z', 'venue' => 'SoFi Stadium'],
            ['match' => 99, 'round' => 'quarter_final', 'utc' => '2026-07-11T21:00:00Z', 'venue' => 'Hard Rock Stadium'],
            ['match' => 100, 'round' => 'quarter_final', 'utc' => '2026-07-12T01:00:00Z', 'venue' => 'Arrowhead Stadium'],
            ['match' => 101, 'round' => 'semi_final', 'utc' => '2026-07-14T19:00:00Z', 'venue' => 'AT&T Stadium'],
            ['match' => 102, 'round' => 'semi_final', 'utc' => '2026-07-15T19:00:00Z', 'venue' => 'Mercedes-Benz Stadium'],
            ['match' => 103, 'round' => 'third_place', 'utc' => '2026-07-18T21:00:00Z', 'venue' => 'Hard Rock Stadium'],
            ['match' => 104, 'round' => 'final', 'utc' => '2026-07-19T19:00:00Z', 'venue' => 'MetLife Stadium'],
        ];
    }

    /**
     * The official 2026 FIFA World Cup group-stage schedule with kickoff times
     * already converted to UTC (ISO 8601).
     *
     * @return list<array{match:int, utc:string, group:string, home:string, away:string, venue:string}>
     */
    private function groupStageSchedule(): array
    {
        return [
            ['match' => 1, 'utc' => '2026-06-11T19:00:00Z', 'group' => 'A', 'home' => 'MEX', 'away' => 'RSA', 'venue' => 'Estadio Azteca'],
            ['match' => 2, 'utc' => '2026-06-12T02:00:00Z', 'group' => 'A', 'home' => 'KOR', 'away' => 'CZE', 'venue' => 'Estadio Akron'],
            ['match' => 3, 'utc' => '2026-06-12T19:00:00Z', 'group' => 'B', 'home' => 'CAN', 'away' => 'BIH', 'venue' => 'BMO Field'],
            ['match' => 4, 'utc' => '2026-06-13T01:00:00Z', 'group' => 'D', 'home' => 'USA', 'away' => 'PAR', 'venue' => 'SoFi Stadium'],
            ['match' => 5, 'utc' => '2026-06-13T19:00:00Z', 'group' => 'B', 'home' => 'QAT', 'away' => 'SUI', 'venue' => "Levi's Stadium"],
            ['match' => 6, 'utc' => '2026-06-13T22:00:00Z', 'group' => 'C', 'home' => 'BRA', 'away' => 'MAR', 'venue' => 'MetLife Stadium'],
            ['match' => 7, 'utc' => '2026-06-14T01:00:00Z', 'group' => 'C', 'home' => 'HAI', 'away' => 'SCO', 'venue' => 'Gillette Stadium'],
            ['match' => 8, 'utc' => '2026-06-14T04:00:00Z', 'group' => 'D', 'home' => 'AUS', 'away' => 'TUR', 'venue' => 'BC Place'],
            ['match' => 9, 'utc' => '2026-06-14T17:00:00Z', 'group' => 'E', 'home' => 'GER', 'away' => 'CUW', 'venue' => 'NRG Stadium'],
            ['match' => 10, 'utc' => '2026-06-14T20:00:00Z', 'group' => 'F', 'home' => 'NED', 'away' => 'JPN', 'venue' => 'AT&T Stadium'],
            ['match' => 11, 'utc' => '2026-06-14T23:00:00Z', 'group' => 'E', 'home' => 'CIV', 'away' => 'ECU', 'venue' => 'Lincoln Financial Field'],
            ['match' => 12, 'utc' => '2026-06-15T02:00:00Z', 'group' => 'F', 'home' => 'SWE', 'away' => 'TUN', 'venue' => 'Estadio BBVA'],
            ['match' => 13, 'utc' => '2026-06-15T17:00:00Z', 'group' => 'H', 'home' => 'ESP', 'away' => 'CPV', 'venue' => 'Mercedes-Benz Stadium'],
            ['match' => 14, 'utc' => '2026-06-15T22:00:00Z', 'group' => 'G', 'home' => 'BEL', 'away' => 'EGY', 'venue' => 'Lumen Field'],
            ['match' => 15, 'utc' => '2026-06-15T22:00:00Z', 'group' => 'H', 'home' => 'KSA', 'away' => 'URU', 'venue' => 'Hard Rock Stadium'],
            ['match' => 16, 'utc' => '2026-06-16T04:00:00Z', 'group' => 'G', 'home' => 'IRN', 'away' => 'NZL', 'venue' => 'SoFi Stadium'],
            ['match' => 17, 'utc' => '2026-06-16T19:00:00Z', 'group' => 'I', 'home' => 'FRA', 'away' => 'SEN', 'venue' => 'MetLife Stadium'],
            ['match' => 18, 'utc' => '2026-06-16T22:00:00Z', 'group' => 'I', 'home' => 'IRQ', 'away' => 'NOR', 'venue' => 'Gillette Stadium'],
            ['match' => 19, 'utc' => '2026-06-17T01:00:00Z', 'group' => 'J', 'home' => 'ARG', 'away' => 'ALG', 'venue' => 'Arrowhead Stadium'],
            ['match' => 20, 'utc' => '2026-06-17T04:00:00Z', 'group' => 'J', 'home' => 'AUT', 'away' => 'JOR', 'venue' => "Levi's Stadium"],
            ['match' => 21, 'utc' => '2026-06-17T17:00:00Z', 'group' => 'K', 'home' => 'POR', 'away' => 'COD', 'venue' => 'NRG Stadium'],
            ['match' => 22, 'utc' => '2026-06-17T20:00:00Z', 'group' => 'L', 'home' => 'ENG', 'away' => 'CRO', 'venue' => 'AT&T Stadium'],
            ['match' => 23, 'utc' => '2026-06-17T23:00:00Z', 'group' => 'L', 'home' => 'GHA', 'away' => 'PAN', 'venue' => 'BMO Field'],
            ['match' => 24, 'utc' => '2026-06-18T02:00:00Z', 'group' => 'K', 'home' => 'UZB', 'away' => 'COL', 'venue' => 'Estadio Azteca'],
            ['match' => 25, 'utc' => '2026-06-18T16:00:00Z', 'group' => 'A', 'home' => 'CZE', 'away' => 'RSA', 'venue' => 'Mercedes-Benz Stadium'],
            ['match' => 26, 'utc' => '2026-06-18T19:00:00Z', 'group' => 'B', 'home' => 'SUI', 'away' => 'BIH', 'venue' => 'SoFi Stadium'],
            ['match' => 27, 'utc' => '2026-06-18T22:00:00Z', 'group' => 'B', 'home' => 'CAN', 'away' => 'QAT', 'venue' => 'BC Place'],
            ['match' => 28, 'utc' => '2026-06-19T03:00:00Z', 'group' => 'A', 'home' => 'MEX', 'away' => 'KOR', 'venue' => 'Estadio Akron'],
            ['match' => 29, 'utc' => '2026-06-19T19:00:00Z', 'group' => 'D', 'home' => 'USA', 'away' => 'AUS', 'venue' => 'Lumen Field'],
            ['match' => 30, 'utc' => '2026-06-19T22:00:00Z', 'group' => 'C', 'home' => 'SCO', 'away' => 'MAR', 'venue' => 'Gillette Stadium'],
            ['match' => 31, 'utc' => '2026-06-20T01:00:00Z', 'group' => 'C', 'home' => 'BRA', 'away' => 'HAI', 'venue' => 'Lincoln Financial Field'],
            ['match' => 32, 'utc' => '2026-06-20T04:00:00Z', 'group' => 'D', 'home' => 'TUR', 'away' => 'PAR', 'venue' => "Levi's Stadium"],
            ['match' => 33, 'utc' => '2026-06-20T17:00:00Z', 'group' => 'F', 'home' => 'NED', 'away' => 'SWE', 'venue' => 'NRG Stadium'],
            ['match' => 34, 'utc' => '2026-06-20T20:00:00Z', 'group' => 'E', 'home' => 'GER', 'away' => 'CIV', 'venue' => 'BMO Field'],
            ['match' => 35, 'utc' => '2026-06-21T00:00:00Z', 'group' => 'E', 'home' => 'ECU', 'away' => 'CUW', 'venue' => 'Arrowhead Stadium'],
            ['match' => 36, 'utc' => '2026-06-21T04:00:00Z', 'group' => 'F', 'home' => 'TUN', 'away' => 'JPN', 'venue' => 'Estadio BBVA'],
            ['match' => 37, 'utc' => '2026-06-21T16:00:00Z', 'group' => 'H', 'home' => 'ESP', 'away' => 'KSA', 'venue' => 'Mercedes-Benz Stadium'],
            ['match' => 38, 'utc' => '2026-06-21T19:00:00Z', 'group' => 'G', 'home' => 'BEL', 'away' => 'IRN', 'venue' => 'SoFi Stadium'],
            ['match' => 39, 'utc' => '2026-06-21T22:00:00Z', 'group' => 'H', 'home' => 'URU', 'away' => 'CPV', 'venue' => 'Hard Rock Stadium'],
            ['match' => 40, 'utc' => '2026-06-22T01:00:00Z', 'group' => 'G', 'home' => 'NZL', 'away' => 'EGY', 'venue' => 'BC Place'],
            ['match' => 41, 'utc' => '2026-06-22T17:00:00Z', 'group' => 'J', 'home' => 'ARG', 'away' => 'AUT', 'venue' => 'AT&T Stadium'],
            ['match' => 42, 'utc' => '2026-06-22T21:00:00Z', 'group' => 'I', 'home' => 'FRA', 'away' => 'IRQ', 'venue' => 'Lincoln Financial Field'],
            ['match' => 43, 'utc' => '2026-06-23T00:00:00Z', 'group' => 'I', 'home' => 'NOR', 'away' => 'SEN', 'venue' => 'MetLife Stadium'],
            ['match' => 44, 'utc' => '2026-06-23T03:00:00Z', 'group' => 'J', 'home' => 'JOR', 'away' => 'ALG', 'venue' => "Levi's Stadium"],
            ['match' => 45, 'utc' => '2026-06-23T17:00:00Z', 'group' => 'K', 'home' => 'POR', 'away' => 'UZB', 'venue' => 'NRG Stadium'],
            ['match' => 46, 'utc' => '2026-06-23T20:00:00Z', 'group' => 'L', 'home' => 'ENG', 'away' => 'GHA', 'venue' => 'Gillette Stadium'],
            ['match' => 47, 'utc' => '2026-06-23T23:00:00Z', 'group' => 'L', 'home' => 'PAN', 'away' => 'CRO', 'venue' => 'BMO Field'],
            ['match' => 48, 'utc' => '2026-06-24T02:00:00Z', 'group' => 'K', 'home' => 'COL', 'away' => 'COD', 'venue' => 'Estadio Akron'],
            ['match' => 49, 'utc' => '2026-06-24T19:00:00Z', 'group' => 'B', 'home' => 'SUI', 'away' => 'CAN', 'venue' => 'BC Place'],
            ['match' => 50, 'utc' => '2026-06-24T19:00:00Z', 'group' => 'B', 'home' => 'BIH', 'away' => 'QAT', 'venue' => 'Lumen Field'],
            ['match' => 51, 'utc' => '2026-06-24T22:00:00Z', 'group' => 'C', 'home' => 'SCO', 'away' => 'BRA', 'venue' => 'Hard Rock Stadium'],
            ['match' => 52, 'utc' => '2026-06-24T22:00:00Z', 'group' => 'C', 'home' => 'MAR', 'away' => 'HAI', 'venue' => 'Mercedes-Benz Stadium'],
            ['match' => 53, 'utc' => '2026-06-25T01:00:00Z', 'group' => 'A', 'home' => 'CZE', 'away' => 'MEX', 'venue' => 'Estadio Azteca'],
            ['match' => 54, 'utc' => '2026-06-25T01:00:00Z', 'group' => 'A', 'home' => 'RSA', 'away' => 'KOR', 'venue' => 'Estadio BBVA'],
            ['match' => 55, 'utc' => '2026-06-25T20:00:00Z', 'group' => 'E', 'home' => 'ECU', 'away' => 'GER', 'venue' => 'MetLife Stadium'],
            ['match' => 56, 'utc' => '2026-06-25T20:00:00Z', 'group' => 'E', 'home' => 'CUW', 'away' => 'CIV', 'venue' => 'Lincoln Financial Field'],
            ['match' => 57, 'utc' => '2026-06-25T23:00:00Z', 'group' => 'F', 'home' => 'JPN', 'away' => 'SWE', 'venue' => 'AT&T Stadium'],
            ['match' => 58, 'utc' => '2026-06-25T23:00:00Z', 'group' => 'F', 'home' => 'TUN', 'away' => 'NED', 'venue' => 'Arrowhead Stadium'],
            ['match' => 59, 'utc' => '2026-06-26T02:00:00Z', 'group' => 'D', 'home' => 'TUR', 'away' => 'USA', 'venue' => 'SoFi Stadium'],
            ['match' => 60, 'utc' => '2026-06-26T02:00:00Z', 'group' => 'D', 'home' => 'PAR', 'away' => 'AUS', 'venue' => "Levi's Stadium"],
            ['match' => 61, 'utc' => '2026-06-26T19:00:00Z', 'group' => 'I', 'home' => 'NOR', 'away' => 'FRA', 'venue' => 'Gillette Stadium'],
            ['match' => 62, 'utc' => '2026-06-26T19:00:00Z', 'group' => 'I', 'home' => 'SEN', 'away' => 'IRQ', 'venue' => 'BMO Field'],
            ['match' => 63, 'utc' => '2026-06-27T00:00:00Z', 'group' => 'H', 'home' => 'URU', 'away' => 'ESP', 'venue' => 'Estadio Akron'],
            ['match' => 64, 'utc' => '2026-06-27T00:00:00Z', 'group' => 'H', 'home' => 'CPV', 'away' => 'KSA', 'venue' => 'NRG Stadium'],
            ['match' => 65, 'utc' => '2026-06-27T03:00:00Z', 'group' => 'G', 'home' => 'EGY', 'away' => 'IRN', 'venue' => 'Lumen Field'],
            ['match' => 66, 'utc' => '2026-06-27T03:00:00Z', 'group' => 'G', 'home' => 'NZL', 'away' => 'BEL', 'venue' => 'BC Place'],
            ['match' => 67, 'utc' => '2026-06-27T21:00:00Z', 'group' => 'L', 'home' => 'PAN', 'away' => 'ENG', 'venue' => 'MetLife Stadium'],
            ['match' => 68, 'utc' => '2026-06-27T21:00:00Z', 'group' => 'L', 'home' => 'CRO', 'away' => 'GHA', 'venue' => 'Lincoln Financial Field'],
            ['match' => 69, 'utc' => '2026-06-27T23:30:00Z', 'group' => 'K', 'home' => 'COL', 'away' => 'POR', 'venue' => 'Hard Rock Stadium'],
            ['match' => 70, 'utc' => '2026-06-27T23:30:00Z', 'group' => 'K', 'home' => 'COD', 'away' => 'UZB', 'venue' => 'Mercedes-Benz Stadium'],
            ['match' => 71, 'utc' => '2026-06-28T02:00:00Z', 'group' => 'J', 'home' => 'ALG', 'away' => 'AUT', 'venue' => 'Arrowhead Stadium'],
            ['match' => 72, 'utc' => '2026-06-28T02:00:00Z', 'group' => 'J', 'home' => 'JOR', 'away' => 'ARG', 'venue' => 'AT&T Stadium'],
        ];
    }
}
