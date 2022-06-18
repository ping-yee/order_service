<?php

namespace App\Models\v1;

use CodeIgniter\Model;

use App\Entities\v1\OrderEntity;

class OrderModel extends Model
{
    protected $DBGroup          = USE_DB_GROUP;
    protected $table            = 'order';
    protected $primaryKey       = 'o_key';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = OrderEntity::class;
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = ['u_key','discount'];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules      = [];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];

    /**
     * 新增訂單與訂單商品的 Transcation
     *
     * @param string $o_key
     * @param integer $u_key
     * @param integer $discount
     * @param array $productDetailArr
     * @return integer|null
     */
    public function createOrderTranscation(string $o_key, int $u_key, int $discount, array $productDetailArr): ?int
    {
        $total      = 0;
        $now        = date("Y-m-d H:i:s");
        $orderData  = [
            "o_key"      => $o_key,
            "u_key"      => $u_key,
            "discount"   => $discount,
            "created_at" => $now,
            "updated_at" => $now
        ];
        

        try {
            $this->db->transStart();

            $this->db->table("order")
                     ->insert($orderData);

            foreach ($productDetailArr as $product) {
                $data = [
                    "o_key"      => $o_key,
                    "p_key"      => $product["p_key"],
                    "price"      => $product["price"],
                    "created_at" => $now,
                    "updated_at" => $now
                ];

                $total += $product["price"];

                $this->db->table("order_product")
                         ->insert($data);
            }
            
            $total -= $orderData["discount"];

            $result = $this->db->transComplete();

            if ($result) {
                return $total;
            }else{
                return null;
            }

        } catch (\Exception $e) {
            log_message('error', '[ERROR] {exception}', ['exception' => $e]);
            return null;
        }
    }

    /**
     * 刪除訂單與訂單商品 transcation
     *
     * @param integer $orderKey
     * @return bool
     */
    public function deleteOrderTranscation(string $orderKey):bool
    {
        try {
            $this->db->transStart();

            $time = [
                "deleted_at" => date("Y-m-d H:i:s")
            ];

            $this->db->table("order")
                     ->where("o_key",$orderKey)
                     ->update($time);
            
            $this->db->table("order_product")
                     ->where("o_key", $orderKey)
                     ->update($time);

            $result = $this->db->transComplete();
        } catch (\Exception $e) {
            log_message('error', '[ERROR] {exception}', ['exception' => $e]);
            return false;
        }
        return $result;
    }
}
