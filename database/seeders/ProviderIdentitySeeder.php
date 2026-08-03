<?php

namespace Database\Seeders;

use App\Models\ProviderIdentity;
use Illuminate\Database\Seeder;

class ProviderIdentitySeeder extends Seeder
{
    /**
     * Seed simulated national identity records used by the mock provider.
     *
     * Ages are derived from birth dates relative to "now" so the records
     * stay correct over time. All identifiers are fictional.
     */
    public function run(): void
    {
        $identities = [
            ['national_id' => '185026948372154', 'full_name' => 'Hana Bekele', 'birth_date' => '2003-04-11', 'status' => 'active'],
            ['national_id' => '185038420915677', 'full_name' => 'Dawit Tesfaye', 'birth_date' => '1997-08-02', 'status' => 'active'],
            ['national_id' => '185041759284301', 'full_name' => 'Meron Girma', 'birth_date' => '1985-01-19', 'status' => 'active'],
            ['national_id' => '185052613847902', 'full_name' => 'Abebe Tadesse', 'birth_date' => '1972-12-25', 'status' => 'active'],
            ['national_id' => '185063384725618', 'full_name' => 'Sara Alemayehu', 'birth_date' => '2010-06-30', 'status' => 'active'],
            ['national_id' => '185074159362840', 'full_name' => 'Yonas Kebede', 'birth_date' => '2005-03-17', 'status' => 'active'],
            ['national_id' => '185085237491603', 'full_name' => 'Liya Mulugeta', 'birth_date' => '2016-09-08', 'status' => 'active'],
            ['national_id' => '185096184527339', 'full_name' => 'Samuel Haile', 'birth_date' => '2001-11-23', 'status' => 'active'],
            ['national_id' => '185107362594081', 'full_name' => 'Betelhem Assefa', 'birth_date' => '1990-02-14', 'status' => 'active'],
            ['national_id' => '185118457203694', 'full_name' => 'Mekonnen Berhanu', 'birth_date' => '1965-07-07', 'status' => 'active'],
            ['national_id' => '185129384651207', 'full_name' => 'Selam Tsegaye', 'birth_date' => '2007-05-21', 'status' => 'active'],
            ['national_id' => '185130274958316', 'full_name' => 'Natnael Fikre', 'birth_date' => '2013-01-30', 'status' => 'active'],
            ['national_id' => '185141583902674', 'full_name' => 'Rahel Gizaw', 'birth_date' => '1999-09-09', 'status' => 'blocked'],
            ['national_id' => '185152906478215', 'full_name' => 'Ermias Wolde', 'birth_date' => '1994-04-01', 'status' => 'lost'],
            ['national_id' => '185163582719046', 'full_name' => 'Mahlet Demissie', 'birth_date' => '2004-10-05', 'status' => 'active'],
        ];

        foreach ($identities as $identity) {
            ProviderIdentity::firstOrCreate(
                ['national_id' => $identity['national_id']],
                $identity,
            );
        }
    }
}
