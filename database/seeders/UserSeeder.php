<?php

namespace Database\Seeders;

use DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {


        // DB::table('users')
        // ->insert(
        //     [
        //     [
        //         'id' => 1,
        //         'name' => 'Admin',
        //         'email' => 'admin@gmail.com',
        //         'email_verified_at' => null,
        //         'password' => '$2y$12$.kKxXbWD9VicJ6UoFbwq0.tLs8A5CGW2rJ7fpu.Gttklz19lwGXSK',
        //         'user_type' => 1,
        //         'services' => null,
        //         'org_id' => null,
        //         'is_user_customer' => null,
        //         'remember_token' => null,
        //         'created_at' => '2024-05-16 20:37:52',
        //         'updated_at' => '2024-05-16 20:37:52',
        //         'is_user_organizational' => null,
        //     ],
        //     [
        //         'id' => 2,
        //         'name' => 'Test User',
        //         'email' => 'testuser@gmail.com',
        //         'email_verified_at' => null,
        //         'password' => '$2y$12$Yi4tMZw0k/9WOAsd8P8l8eXA6VHkG7iLz4eTxAPpxXJDAV7UBsKnK',
        //         'user_type' => 0,
        //         'services' => '[1,4,2,3]',
        //         'org_id' => '1',
        //         'is_user_customer' => null,
        //         'remember_token' => null,
        //         'created_at' => '2024-06-12 19:03:07',
        //         'updated_at' => '2024-08-27 11:00:26',
        //         'is_user_organizational' => null,
        //     ],
        //     [
        //         'id' => 3,
        //         'name' => 'Fanny Briese',
        //         'email' => 'fanny.briese@enertrag.com',
        //         'email_verified_at' => null,
        //         'password' => '$2y$12$Nlv/KJs8Dlol3X1fQs9y8.Yk/rPQKHeS9q67DCLJgrjRZE/K5XKlC',
        //         'user_type' => 0,
        //         'services' => '[3]',
        //         'org_id' => null,
        //         'is_user_customer' => null,
        //         'remember_token' => null,
        //         'created_at' => '2024-08-21 09:41:39',
        //         'updated_at' => '2024-08-29 07:44:09',
        //         'is_user_organizational' => null,
        //     ],
        //     [
        //         'id' => 5,
        //         'name' => 'Mary Michaelis',
        //         'email' => 'mary.michaelis@enertrag.com',
        //         'email_verified_at' => null,
        //         'password' => '$2y$12$4kFgDgkQ7uWZ3DjW3/TibuQ7ZflDrPtmBjsDqtkDMHHBT3h6TNkfe',
        //         'user_type' => 0,
        //         'services' => '[3]',
        //         'org_id' => null,
        //         'is_user_customer' => null,
        //         'remember_token' => null,
        //         'created_at' => '2024-08-21 09:43:50',
        //         'updated_at' => '2024-08-29 07:46:05',
        //         'is_user_organizational' => null,
        //     ],
        //     [
        //         'id' => 10,
        //         'name' => 'Mandy Licht',
        //         'email' => 'mandy.licht@enertrag.com',
        //         'email_verified_at' => null,
        //         'password' => '$2y$12$spQp6XGZCDOJWU0mBGOgUua.R2mAqdW53Vu941NN/fkcdT0Lcn7Mi',
        //         'user_type' => 0,
        //         'services' => '[3]',
        //         'org_id' => null,
        //         'is_user_customer' => null,
        //         'remember_token' => null,
        //         'created_at' => '2024-09-02 08:11:37',
        //         'updated_at' => '2024-09-02 08:11:37',
        //         'is_user_organizational' => null,
        //     ],
        //     [
        //         'id' => 11,
        //         'name' => 'Susann Golz',
        //         'email' => 'susann.golz@enertrag.com',
        //         'email_verified_at' => null,
        //         'password' => '$2y$12$rTJRO8WcW6lR5wOeuqmLfuu9zPa4Iyca4u3xVQQqKDfGJzis9GWPK',
        //         'user_type' => 0,
        //         'services' => '[3]',
        //         'org_id' => null,
        //         'is_user_customer' => null,
        //         'remember_token' => null,
        //         'created_at' => '2024-09-02 08:13:29',
        //         'updated_at' => '2024-09-02 08:13:29',
        //         'is_user_organizational' => null,
        //     ],
        //     [
        //         'id' => 12,
        //         'name' => 'Eileen Littmann',
        //         'email' => 'eileen.littmann@enertrag.com',
        //         'email_verified_at' => null,
        //         'password' => '$2y$12$nDMpZbyW0xPiNlkHSyCFbuRSaWQdR/EdqulGvmBO/VzC16so9Tyo6',
        //         'user_type' => 0,
        //         'services' => '[3]',
        //         'org_id' => null,
        //         'is_user_customer' => null,
        //         'remember_token' => null,
        //         'created_at' => '2024-09-02 08:17:05',
        //         'updated_at' => '2024-09-04 08:50:45',
        //         'is_user_organizational' => null,
        //     ],
        //     [
        //         'id' => 13,
        //         'name' => 'Lucia Michael',
        //         'email' => 'lucia.michael@enertrag.com',
        //         'email_verified_at' => null,
        //         'password' => '$2y$12$.k8iwdc5z4Ox9Wo8dKLHUeHC5QYdRTwAgZ6IS6yM1zB9C4F8cYVx6',
        //         'user_type' => 0,
        //         'services' => '[3]',
        //         'org_id' => null,
        //         'is_user_customer' => null,
        //         'remember_token' => null,
        //         'created_at' => '2024-09-02 08:19:44',
        //         'updated_at' => '2024-09-02 08:19:44',
        //         'is_user_organizational' => null,
        //     ],
        //     [
        //         'id' => 14,
        //         'name' => 'Jessica Zittlau',
        //         'email' => 'jessica.zittlau@enertrag.com',
        //         'email_verified_at' => null,
        //         'password' => '$2y$12$0sw2skVP2LPPL4JQvUFgh.MFgynVbNS7Eh6/cnZHbVsm0qRbeOiMy',
        //         'user_type' => 0,
        //         'services' => '[3]',
        //         'org_id' => null,
        //         'is_user_customer' => null,
        //         'remember_token' => null,
        //         'created_at' => '2024-09-02 08:20:58',
        //         'updated_at' => '2024-09-02 08:20:58',
        //         'is_user_organizational' => null,
        //     ],
        //     [
        //         'id' => 15,
        //         'name' => 'Doris Semler',
        //         'email' => 'doris.semler@enertrag.com',
        //         'email_verified_at' => null,
        //         'password' => '$2y$12$YwXmRuMjE/5Zit.3cjqP9u555oZJxFNOh2pHDqHcknoHAMPD23DmO',
        //         'user_type' => 0,
        //         'services' => '[3]',
        //         'org_id' => null,
        //         'is_user_customer' => null,
        //         'remember_token' => null,
        //         'created_at' => '2024-09-02 08:22:03',
        //         'updated_at' => '2024-09-02 08:22:03',
        //         'is_user_organizational' => null,
        //     ],
        //     [
        //         'id' => 16,
        //         'name' => 'Vanessa Manthey',
        //         'email' => 'vanessa.manthey@enertrag.com',
        //         'email_verified_at' => null,
        //         'password' => '$2y$12$GOTHNzvTpcMTOwPkej1aKeOHd.d5HiLip4FDAqqesLLQFTqMcc9Z.',
        //         'user_type' => 0,
        //         'services' => '[3]',
        //         'org_id' => null,
        //         'is_user_customer' => null,
        //         'remember_token' => null,
        //         'created_at' => '2024-09-02 08:22:53',
        //         'updated_at' => '2024-09-02 08:22:53',
        //         'is_user_organizational' => null,
        //     ],
        //     [
        //         'id' => 17,
        //         'name' => 'Laura Kaulitz',
        //         'email' => 'laura.kaulitz@enertrag.com',
        //         'email_verified_at' => null,
        //         'password' => '$2y$12$l7uAd4W40KSuaVv3XaRzDeIVPPTHeicbvXbtaDHbz7TpBs458evNO',
        //         'user_type' => 0,
        //         'services' => '[3]',
        //         'org_id' => null,
        //         'is_user_customer' => null,
        //         'remember_token' => null,
        //         'created_at' => '2024-09-02 08:23:57',
        //         'updated_at' => '2024-09-02 08:23:57',
        //         'is_user_organizational' => null,
        //     ],
        //     [
        //         'id' => 18,
        //         'name' => 'Cretschmar',
        //         'email' => 'test@cretschmar.de',
        //         'email_verified_at' => null,
        //         'password' => '$2y$12$HsUzBszN51OIokSNlAc/u..Pwz5GTr/dB.6NhAd.t1SYx3F8Lp5xi',
        //         'user_type' => 0,
        //         'services' => '[4]',
        //         'org_id' => null,
        //         'is_user_customer' => null,
        //         'remember_token' => null,
        //         'created_at' => '2024-09-02 15:38:42',
        //         'updated_at' => '2024-09-02 15:38:42',
        //         'is_user_organizational' => null,
        //     ],
        //     [
        //         'id' => 91,
        //         'name' => 'HaseeB',
        //         'email' => 'haseeB@gmail.com',
        //         'email_verified_at' => null,
        //         'password' => '$2y$12$iVX3fYq7S98aatLNlXi2gOFBenhuTgKgdNTLlv.rIWxTUOA920Flm',
        //         'user_type' => 0,
        //         'services' => '[1,2,3,4]',
        //         'org_id' => '1',
        //         'is_user_customer' => null,
        //         'remember_token' => null,
        //         'created_at' => '2024-09-20 12:49:39',
        //         'updated_at' => '2024-09-20 12:49:39',
        //         'is_user_organizational' => 1,
        //     ],
        //     [
        //         'id' => 94,
        //         'name' => 'ahsan2',
        //         'email' => 'ahsan2@gmail.com',
        //         'email_verified_at' => null,
        //         'password' => '$2y$12$0cv9cTFTBvY98Te/VoACUuMUidQpSMSXaCGKLyHSpjMdqUbAyVhzC',
        //         'user_type' => 0,
        //         'services' => '[1,2,4]',
        //         'org_id' => '1',
        //         'is_user_customer' => null,
        //         'remember_token' => null,
        //         'created_at' => '2024-09-20 13:16:11',
        //         'updated_at' => '2024-09-20 13:18:52',
        //         'is_user_organizational' => 1,
        //     ],
        //     [
        //         'id' => 95,
        //         'name' => 'ahsan3',
        //         'email' => 'ahsan3@gmail.com',
        //         'email_verified_at' => null,
        //         'password' => '$2y$12$.vamxHL.oZUPUq1tFinh4.eYgJ.iTAo1QLB4qBWNRnHyYpXhXnQ5m',
        //         'user_type' => 0,
        //         'services' => '[1,2,4]',
        //         'org_id' => '1',
        //         'is_user_customer' => null,
        //         'remember_token' => null,
        //         'created_at' => '2024-09-20 13:17:52',
        //         'updated_at' => '2024-09-20 13:30:56',
        //         'is_user_organizational' => null,
        //     ],
        //     [
        //         'id' => 99,
        //         'name' => 'testing3',
        //         'email' => 'testing3@gmail.com',
        //         'email_verified_at' => null,
        //         'password' => '$2y$12$fuuOngDkgYExSKp4Fce6BOEerYBB8eVDoz14/b5SUptfcD1drdw0u',
        //         'user_type' => 0,
        //         'services' => '[3,4,2,1]',
        //         'org_id' => '2',
        //         'is_user_customer' => null,
        //         'remember_token' => null,
        //         'created_at' => '2024-09-20 13:59:38',
        //         'updated_at' => '2024-09-20 14:01:20',
        //         'is_user_organizational' => null,
        //     ],
        //     [
        //         'id' => 100,
        //         'name' => 'Cretschmar Admin',
        //         'email' => 'uwe.leven@cretschmar.de',
        //         'email_verified_at' => null,
        //         'password' => '$2y$12$WBtRLIOSXJBWGPOBhLqa8eXOnTL9hbMzXplarDFABwJwWcfiPptY.',
        //         'user_type' => 0,
        //         'services' => '[4]',
        //         'org_id' => null,
        //         'is_user_customer' => 1,
        //         'remember_token' => null,
        //         'created_at' => '2024-09-24 12:48:45',
        //         'updated_at' => '2024-09-30 16:09:48',
        //         'is_user_organizational' => 1,
        //     ],
        //     [
        //         'id' => 102,
        //         'name' => 'Cretschmar User',
        //         'email' => 'logistik@cretschmar.de',
        //         'email_verified_at' => null,
        //         'password' => '$2y$12$QoG5ytqZyjnjZAhe3pkBOuueeY4SDUDsIEkIm/OSAoeSKlsIc49p2',
        //         'user_type' => 0,
        //         'services' => '[4]',
        //         'org_id' => null,
        //         'is_user_customer' => null,
        //         'remember_token' => null,
        //         'created_at' => '2024-09-24 13:11:01',
        //         'updated_at' => '2024-09-24 13:35:35',
        //         'is_user_organizational' => null,
        //     ],
        //     [
        //         'id' => 111,
        //         'name' => 'test5',
        //         'email' => 'test5@gmail.com',
        //         'email_verified_at' => null,
        //         'password' => '$2y$12$u0GL7RfWPhqCS9Jj1L3iTuj0V2hMork9Q9re9rh01nsy.cj0k6HWa',
        //         'user_type' => 0,
        //         'services' => '[1,2,3]',
        //         'org_id' => '30',
        //         'is_user_customer' => null,
        //         'remember_token' => null,
        //         'created_at' => '2024-09-26 08:25:04',
        //         'updated_at' => '2024-09-26 08:25:04',
        //         'is_user_organizational' => null,
        //     ],
        //     [
        //         'id' => 114,
        //         'name' => 'Torsten Tester',
        //         'email' => 't.tester@gmail.com',
        //         'email_verified_at' => null,
        //         'password' => '$2y$12$CC2KOXlUGSFYLgRthKa5f.t4J29EvBoeAw3GSD7eFu4Mgj.ic/BWy',
        //         'user_type' => 0,
        //         'services' => '[1,2,3]',
        //         'org_id' => '30',
        //         'is_user_customer' => null,
        //         'remember_token' => null,
        //         'created_at' => '2024-09-26 08:46:52',
        //         'updated_at' => '2024-09-26 08:46:52',
        //         'is_user_organizational' => null,
        //     ],
        //     [
        //         'id' => 120,
        //         'name' => 'Torsten Tester',
        //         'email' => 'torsten.tester@cretschmar.de',
        //         'email_verified_at' => null,
        //         'password' => '$2y$12$ixPviHuSQ0V2joZj2jGmIu21B.YMxrqgHym3u/L9cAnLxS3AD1tuK',
        //         'user_type' => 0,
        //         'services' => '[4]',
        //         'org_id' => null,
        //         'is_user_customer' => null,
        //         'remember_token' => null,
        //         'created_at' => '2024-09-27 07:43:53',
        //         'updated_at' => '2024-09-27 07:43:53',
        //         'is_user_organizational' => null,
        //     ],
        //     [
        //         'id' => 123,
        //         'name' => 'Zubair Khan',
        //         'email' => 'test123@gmail.com',
        //         'email_verified_at' => null,
        //         'password' => '$2y$12$1WOI8FLK9WvdEw41VRa/v.9nhPRdPMGH9PNOhBWcnFBx4YGxs24Bq',
        //         'user_type' => 0,
        //         'services' => '[1,2,3]',
        //         'org_id' => '30',
        //         'is_user_customer' => null,
        //         'remember_token' => null,
        //         'created_at' => '2024-09-27 15:07:45',
        //         'updated_at' => '2024-09-27 15:07:45',
        //         'is_user_organizational' => null,
        //     ],
        //     [
        //         'id' => 137,
        //         'name' => 'Free User Cretschmar',
        //         'email' => 'freeusers@cretschmar.de',
        //         'email_verified_at' => null,
        //         'password' => '$2y$12$TxHZsRVfIzcdq5SnfnpPV.GUMe8UAqFOrObg0TfNWf4mRH8Bq9C06',
        //         'user_type' => 0,
        //         'services' => '[4]',
        //         'org_id' => null,
        //         'is_user_customer' => null,
        //         'remember_token' => null,
        //         'created_at' => '2024-10-01 15:59:51',
        //         'updated_at' => '2024-10-01 15:59:51',
        //         'is_user_organizational' => 1,
        //     ],
        //     [
        //         'id' => 143,
        //         'name' => 'Faith Hawkins',
        //         'email' => 'ryriqi@mailinator.com',
        //         'email_verified_at' => null,
        //         'password' => '$2y$12$lfBhMN07TTjkoUIJAv4a0.gEgo28kVnA4FXAG/.xA2KxfMn8QpEB2',
        //         'user_type' => 0,
        //         'services' => '[4]',
        //         'org_id' => null,
        //         'is_user_customer' => null,
        //         'remember_token' => null,
        //         'created_at' => '2024-10-01 13:44:39',
        //         'updated_at' => '2024-10-01 13:44:39',
        //         'is_user_organizational' => 0,
        //     ],
        //     [
        //         'id' => 148,
        //         'name' => 'Test User Cretschmar',
        //         'email' => 'testusercretschmar@gmail.com',
        //         'email_verified_at' => null,
        //         'password' => '$2y$12$mF0jSxbTKkdbtSlGLoB51OZeZ7D6jzwT8UaYjgw1VsxG1La9x0w/K',
        //         'user_type' => 0,
        //         'services' => '[4]',
        //         'org_id' => null,
        //         'is_user_customer' => null,
        //         'remember_token' => null,
        //         'created_at' => '2024-10-01 15:47:59',
        //         'updated_at' => '2024-10-01 15:47:59',
        //         'is_user_organizational' => 0,
        //     ],
        //     [
        //         'id' => 159,
        //         'name' => 'customer',
        //         'email' => 'customer@gmail.com',
        //         'email_verified_at' => null,
        //         'password' => '$2y$12$8wNXq7SU80u12/JA4xkZRuSgrZg7DYk0jrm/b6bY52WLRpT1oRrtu',
        //         'user_type' => 0,
        //         'services' => '[1,2,4,3]',
        //         'org_id' => '1',
        //         'is_user_customer' => 1,
        //         'remember_token' => null,
        //         'created_at' => '2024-10-04 06:50:10',
        //         'updated_at' => '2024-10-04 06:50:10',
        //         'is_user_organizational' => 1,
        //     ],
        //     [
        //         'id' => 160,
        //         'name' => 'Default Organization User',
        //         'email' => 'default_org_user_customer@gmail.com',
        //         'email_verified_at' => null,
        //         'password' => '$2y$12$wjmvUwY879jZohWMAqyyIu3H20CHKs0x/LHOUv0lBW5nywM/f150u',
        //         'user_type' => 0,
        //         'services' => '[1,2,4,3]',
        //         'org_id' => '1',
        //         'is_user_customer' => 0,
        //         'remember_token' => null,
        //         'created_at' => '2024-10-04 06:50:10',
        //         'updated_at' => '2024-10-04 06:50:10',
        //         'is_user_organizational' => 1,
        //     ],
        //     [
        //         'id' => 161,
        //         'name' => 'customer4',
        //         'email' => 'customer4@gmail.com',
        //         'email_verified_at' => null,
        //         'password' => '$2y$12$g7JQ6D.HGiLaC1kx.oa7te.ePj2wdQwpkP9VwcrCUyUB8JLFpZT3K',
        //         'user_type' => 0,
        //         'services' => '[1,2,4,3]',
        //         'org_id' => '1',
        //         'is_user_customer' => 0,
        //         'remember_token' => null,
        //         'created_at' => '2024-10-04 09:51:10',
        //         'updated_at' => '2024-10-04 09:51:10',
        //         'is_user_organizational' => 0,
        //     ],
        //     [
        //         'id' => 162,
        //         'name' => 'customer_b',
        //         'email' => 'customer_b@gmail.com',
        //         'email_verified_at' => null,
        //         'password' => '$2y$12$YZLGPS73YRJLRvjCxpu/Z.FTtH76wRcSunBV65SpXQ4noj7gQRIrm',
        //         'user_type' => 0,
        //         'services' => '[1,2,3,4]',
        //         'org_id' => '1',
        //         'is_user_customer' => 1,
        //         'remember_token' => null,
        //         'created_at' => '2024-10-04 14:52:14',
        //         'updated_at' => '2024-10-04 14:52:35',
        //         'is_user_organizational' => 1,
        //     ],
        //     [
        //         'id' => 163,
        //         'name' => 'Default Organization User',
        //         'email' => 'default_org_user_customer_b@gmail.com',
        //         'email_verified_at' => null,
        //         'password' => '$2y$12$a59MpMzBYkShSLGplYDMN.FNgf9zXqz/YPGzIASH4ztdMbO8dewYu',
        //         'user_type' => 0,
        //         'services' => '[1,2,3,4]',
        //         'org_id' => '1',
        //         'is_user_customer' => 0,
        //         'remember_token' => null,
        //         'created_at' => '2024-10-04 14:52:14',
        //         'updated_at' => '2024-10-04 14:52:35',
        //         'is_user_organizational' => 1,
        //     ],
        //     [
        //         'id' => 164,
        //         'name' => 'customer_k',
        //         'email' => 'customer_k@gmail.com',
        //         'email_verified_at' => null,
        //         'password' => '$2y$12$Vg3hC8Konpf6nG4OQgyBGOpAnFRCKS9nvjeuI9wbxuUYAlX4/GkJm',
        //         'user_type' => 0,
        //         'services' => '[1,2,3,4]',
        //         'org_id' => '1',
        //         'is_user_customer' => 0,
        //         'remember_token' => null,
        //         'created_at' => '2024-10-04 14:53:14',
        //         'updated_at' => '2024-10-04 14:53:14',
        //         'is_user_organizational' => 0,
        //     ],
        // ]);
    }
}
