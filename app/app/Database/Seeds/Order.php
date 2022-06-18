<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class Order extends Seeder
{
    public function run()
    {
        $discountArr = [0,10,20,30,40,50,60,70,80,90,100];
        
        for ($i=0; $i < 100; $i++) {
            $now        = date("Y-m-d H:i:s");
            $u_key      = random_int(1,5);
            $discount   = $discountArr[random_int(0,10)]; 
            $o_key      = sha1($u_key . $discount . $now . random_int(0,10000000));

            $this->db->table("order")->insert([
                'o_key'      => $o_key,
                'u_key'      => $u_key,
                'discount'   => $discount,
                "created_at" => $now,
                "updated_at" => $now
            ]);

            for ($j=0; $j < 10; $j++) {
                $this->db->table("order_product")->insert([
                    'o_key' => $o_key,
                    'p_key' => random_int(1,100),
                    'price' => random_int(0,10000),
                    "created_at" => $now,
                    "updated_at" => $now
                ]);   
            }
        }
    }
}
