<?php

namespace App\Controllers\Dtm;

use CodeIgniter\API\ResponseTrait;

use App\Controllers\Dtm\BaseController;
use App\Models\v1\BusinessLogic\OrderBusinessLogic;
use App\Models\v1\BusinessLogic\OrderProductBusinessLogic;
use App\Models\v1\OrderModel;
use App\Entities\v1\OrderEntity;
use App\Services\User;

class OrderController extends BaseController
{
    use ResponseTrait;

    private $u_key;

    function __construct()
    {
        $this->u_key = User::getUserKey();
    }

    /**
     * [POST] /api/vDtm/order/list
     * 取得所有的訂單清單
     *
     * @return void
     */
    public function index()
    {
        $data = $this->request->getJSON(true);

        $limit  = $data["limit"]  ?? 10;
        $offset = $data["offset"] ?? 0;
        $isDesc = $data["isDesc"] ?? "desc";

        $orderModel  = new OrderModel();
        $orderEntity = new OrderEntity();

        $query  = $orderModel->orderBy("created_at",$isDesc ? "DESC" : "ASC");
        $query->where("u_key", $this->u_key);
        $amount = $query->countAllResults(false);
        $orders = $query->findAll($limit,$offset);

        $data = [
            "list"   => [],
            "amount" => $amount
        ];

        if($orders){
            foreach ($orders as $orderEntity) {
                $orderData = [
                    "u_key"     => $orderEntity->u_key,
                    "discount"  => $orderEntity->discount,
                    "createdAt" => $orderEntity->createdAt,
                    "updatedAt" => $orderEntity->updatedAt
                ];
                $data["list"][] = $orderData;
            }
        }else{
            return $this->fail("無資料",404);
        }

        return $this->respond([
            "msg" => "OK",
            "data" => $data
        ]);
    }

    /**
     * [POST] /api/v1/order/show
     * 取得單一訂單資訊
     *
     * @return void
     */
    public function show()
    {
        $data = $this->request->getJSON(true);

        $orderKey = $data["o_key"] ?? null;

        if(is_null($orderKey)) return $this->fail("無傳入訂單 key",404);

        $orderModel  = new OrderModel();
        $orderEntity = new OrderEntity();

        $orderEntity = $orderModel->where("u_key",$this->u_key)->find($orderKey);

        $orderProdcutsArr = OrderProductBusinessLogic::getOrderProduct($orderKey);
        if(is_null($orderProdcutsArr)) return $this->fail("無商品資料",404);

        if($orderEntity){
            $data = [
                "o_key"     => $orderEntity->o_key,
                "u_key"     => $orderEntity->u_key,
                "discount"  => $orderEntity->discount,
                "products"  => $orderProdcutsArr,
                "createdAt" => $orderEntity->createdAt,
                "updatedAt" => $orderEntity->updatedAt
            ];
        }else{
            return $this->fail("無資料",404);
        }

        return $this->respond([
            "msg"  => "OK",
            "data" => $data
        ]);
    }

    /**
     * [POST] /api/v1/order/create
     * 產生訂單
     *
     * @return void
     */
    public function create()
    {
        $data = $this->request->getJSON(true);

        $o_key            = $data["o_key"] ?? null;
        $discount         = $data["discount"] ?? null;
        $productDetailArr = $data["productDetailArr"] ?? null;

        $u_key = $this->u_key;

        if (is_null($o_key) || is_null($discount) || is_null($productDetailArr)) return $this->fail("請確認輸入資料", 404);

        $orderEntity = OrderBusinessLogic::getOrder($o_key);
        if($orderEntity) return $this->fail("訂單 key 重複輸入",400);

        $orderModel = new OrderModel();

        $orderCreatedTotalOrNull = $orderModel->createOrderTranscation($o_key,$u_key,$discount,$productDetailArr);

        if($orderCreatedTotalOrNull){
            return $this->respond([
                        "msg"   => "OK",
                        "total" => $orderCreatedTotalOrNull
                    ]);
        }else{
            return $this->fail("訂單新增失敗",400);
        }
    }

    /**
     * [POST] /api/vDtm/order/update 
     * 更新訂單折扣
     *  
     * @return void
     */
    public function update()
    {
        $data = $this->request->getJSON(true);

        $o_key            = $data["o_key"] ?? null;
        $discount         = $data["discount"] ?? null;
        $productDetailArr = $data["productDetailArr"] ?? null;

        if(is_null($o_key)) return $this->fail("請傳入訂單 key",404);
        if(is_null($discount) && is_null($productDetailArr)) return $this->fail("請傳入更改折扣或商品",400);

        $orderModel  = new OrderModel();
        
        $orderEntity = $orderModel->find($o_key);

        if(is_null($orderEntity)) return $this->fail("查無此商品",404);

        if(!is_null($productDetailArr)) $result = OrderProductBusinessLogic::update($o_key, $productDetailArr);

        if(!is_null($discount)){
            $orderEntity->discount = $discount;
            $result = $orderModel->update($orderEntity->o_key,$orderEntity->toRawArray(true));
        }

        if($result){
            return $this->respond([
                "msg" => "OK"
            ]);
        }else{
            return $this->fail("更新失敗",400);   
        }
    }

    /**
     * [POST] /api/vDtm/order/delete
     *
     * @param int $orderKey
     * @return void
     */
    public function delete()
    {
        $data = $this->request->getJSON(true);

        $orderKey = $data["o_key"] ?? null;

        if(is_null($orderKey)) return $this->fail("請輸入訂單 key",400);

        $orderModel = new OrderModel();

        $result = $orderModel->deleteOrderTranscation($orderKey);

        if($result){
            return $this->respond([
                "msg" => "OK",
                "res" => $result
            ]);
        }else{
            return $this->fail("刪除訂單失敗",400);
        }
    }
}
