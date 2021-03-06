<?php

App::uses('AppController', 'Controller');

/**
 * DeliverySchedules Controller
 *
 * @property DeliverySchedule $DeliverySchedule
 * @property PaginatorComponent $Paginator
 */
class DeliverySchedulesController extends AppController {

    /**
     * Components
     *
     * @var array
     */
    public $components = array('Paginator');

    /**
     * index method
     *
     * @return void
     */
    public function index() {
        $this->DeliverySchedule->recursive = 0;
        $this->set('deliverySchedules', $this->Paginator->paginate());
    }

    /**
     * view method
     *
     * @throws NotFoundException
     * @param string $id
     * @return void
     */
    public function view($id = null) {
        if (!$this->DeliverySchedule->exists($id)) {
            throw new NotFoundException(__('Invalid delivery schedule'));
        }
        $options = array('conditions' => array('DeliverySchedule.' . $this->DeliverySchedule->primaryKey => $id));
        $this->set('deliverySchedule', $this->DeliverySchedule->find('first', $options));
    }

    /**
     * add method
     *
     * @return void
     */
    public function add() {
        if ($this->request->is('post')) {
            $this->DeliverySchedule->create();
            if ($this->DeliverySchedule->save($this->request->data)) {
                $this->Session->setFlash(__('The delivery schedule has been saved.'), 'default', array('class' => 'alert alert-success'));
                return $this->redirect(array('action' => 'index'));
            } else {
                $this->Session->setFlash(__('The delivery schedule could not be saved. Please, try again.'), 'default', array('class' => 'alert alert-danger'));
            }
        }
        $quotations = $this->DeliverySchedule->Quotation->find('list');
        $this->set(compact('quotations'));
    }

    /**
     * edit method
     *
     * @throws NotFoundException
     * @param string $id
     * @return void
     */
    public function edit($id = null) {
        if (!$this->DeliverySchedule->exists($id)) {
            throw new NotFoundException(__('Invalid delivery schedule'));
        }
        if ($this->request->is(array('post', 'put'))) {
            if ($this->DeliverySchedule->save($this->request->data)) {
                $this->Session->setFlash(__('The delivery schedule has been saved.'), 'default', array('class' => 'alert alert-success'));
                return $this->redirect(array('action' => 'index'));
            } else {
                $this->Session->setFlash(__('The delivery schedule could not be saved. Please, try again.'), 'default', array('class' => 'alert alert-danger'));
            }
        } else {
            $options = array('conditions' => array('DeliverySchedule.' . $this->DeliverySchedule->primaryKey => $id));
            $this->request->data = $this->DeliverySchedule->find('first', $options);
        }
        $quotations = $this->DeliverySchedule->Quotation->find('list');
        $this->set(compact('quotations'));
    }

    /**
     * delete method
     *
     * @throws NotFoundException
     * @param string $id
     * @return void
     */
    public function delete($id = null) {
        $this->DeliverySchedule->id = $id;
        if (!$this->DeliverySchedule->exists()) {
            throw new NotFoundException(__('Invalid delivery schedule'));
        }
        $this->request->onlyAllow('post', 'delete');
        if ($this->DeliverySchedule->delete()) {
            $this->Session->setFlash(__('The delivery schedule has been deleted.'), 'default', array('class' => 'alert alert-success'));
        } else {
            $this->Session->setFlash(__('The delivery schedule could not be deleted. Please, try again.'), 'default', array('class' => 'alert alert-danger'));
        }
        return $this->redirect(array('action' => 'index'));
    }

    public function addSched() {
        $this->loadModel('DeliverySchedProduct');
        $this->loadModel('QuotationProduct');
        $this->autoRender = false;
        $this->response->type('json');
        $data = $this->request->data;
        $delivery_date = $data['delivery_date'];
        $requested_qty = $data['requested_qty'];
        $quotation_product_id = $data['quotation_product_id'];
        $quotation_id = $data['quotation_id'];
        $delivery_time = $data['delivery_time'];
        $mode = $data['mode'];

        //check if there is an ongoing delivery schedule fot the quotation
        $check_sched = $this->DeliverySchedule->find('first', ['conditions' => [
                'DeliverySchedule.status' => 'ongoing',
                'DeliverySchedule.quotation_id' => $quotation_id,
        ]]);
//        pr($check_sched);
        if (count($check_sched) != 0) {
            //add to delivery sched products only

            $this->DeliverySchedProduct->create();
            $this->DeliverySchedProduct->set(array(
                'delivery_schedule_id' => $check_sched['DeliverySchedule']['id'],
                'quotation_product_id' => $quotation_product_id,
                'status' => 'pending',
                'requested_qty' => $requested_qty,
            ));
            if ($this->DeliverySchedProduct->save()) {
                $dsp_id = $this->DeliverySchedule->getLastInsertID();
                $this->QuotationProduct->id = $quotation_product_id;
                $this->QuotationProduct->set(array(
                    'dr_requested' => 1
                ));
                $this->QuotationProduct->save();
                echo json_encode($dsp_id);
            }
        } else {
            //create new dr
            $dateToday = date("Hymds");
            $milliseconds = round(microtime(true) * 1000);
            $newstring = substr($milliseconds, -3);
            $dr_number = $newstring . '' . $dateToday;

            $dr_exist = $this->DeliverySchedule->find('count', array(
                'conditions' => array(
                    'DeliverySchedule.dr_number' => $dr_number
            )));

            if ($dr_exist == 0) {
                $dr_number = $dr_number;
            } else {
                $news = substr($milliseconds, -4);
                $dr_number = $news . '' . $dateToday;
            }
            //create new
            $this->DeliverySchedule->create();
            $this->DeliverySchedule->set(array(
                'dr_number' => $dr_number,
                'status' => 'ongoing',
                'delivery_date' => $delivery_date,
                'delivery_time' => $delivery_time,
                'requested_qty' => $requested_qty,
                'quotation_id' => $quotation_id,
                'mode'=>$mode
            ));
            if ($this->DeliverySchedule->save()) {
                $ds_id = $this->DeliverySchedule->getLastInsertID();

                $this->DeliverySchedProduct->create();
                $this->DeliverySchedProduct->set(array(
                    'delivery_schedule_id' => $ds_id,
                    'quotation_product_id' => $quotation_product_id,
                    'status' => 'pending',
                    'requested_qty' => $requested_qty,
                ));
                if ($this->DeliverySchedProduct->save()) {
                    $dsp_id = $this->DeliverySchedule->getLastInsertID();

                    $this->QuotationProduct->id = $quotation_product_id;
                    $this->QuotationProduct->set(array(
                        'dr_requested' => 1
                    ));
                    $this->QuotationProduct->save();
                    echo json_encode($dsp_id);
                }
            }
        }
    }

    public function requests() {
        //pending, processed, delivered
        $this->loadModel('DeliverySchedProduct');
        $status = $this->params['url']['status'];
        $this->DeliverySchedule->recursive=2;
        
         
        if($status=='ongoing'){
             $requests = $this->DeliverySchedule->find('all', ['conditions' => ['DeliverySchedule.status' => [$status,'pending']]]);
        }else{
             $requests = $this->DeliverySchedule->find('all', ['conditions' => ['DeliverySchedule.status' => $status]]);
        }

        $arr = [];
        foreach ($requests as $req) {
            $delProds = $this->DeliverySchedProduct->find('all', ['conditions' => [
                    'DeliverySchedProduct.delivery_schedule_id' => $req['DeliverySchedule']['id'],
                    'DeliverySchedProduct.status' => 'pending',
            ]]);
            if (!empty(count($delProds))){ 
                array_push($arr, $req);
            }
        }
        
//        pr($arr);exit;
        
        $this->set(compact('arr','status'));
        
        
    }
    
    public function changeStatus(){ 
        $this->autoRender = false;
        $this->response->type('json');
        $data = $this->request->data; 
        
        $delivery_schedule_id = $data['delivery_schedule_id'];
        $status = $data['status'];
        if($status == 'approved'){
            $approved = $this->Auth->user('id');
        }else{
            $approved = 0;
        }
        
        
        $this->DeliverySchedule->id = $delivery_schedule_id;
        $this->DeliverySchedule->set(array(
            'status' => $status,
            'approved' => $approved
        ));
        if ($this->DeliverySchedule->save()) {
            echo json_encode($data);
        }
    }
    
    public function drs() {
        //pending, processed, delivered
        $this->loadModel('DeliverySchedProduct');
        $status = $this->params['url']['status'];
        $this->DeliverySchedule->recursive=2;
        if($status=='ongoing'){
             $requests = $this->DeliverySchedule->find('all', ['conditions' => ['DeliverySchedule.status' => [$status,'pending']]]);
        }else{
             $requests = $this->DeliverySchedule->find('all', ['conditions' => ['DeliverySchedule.status' => $status]]);
        }

        $arr = [];
        foreach ($requests as $req) {
            $delProds = $this->DeliverySchedProduct->find('all', ['conditions' => [
                    'DeliverySchedProduct.delivery_schedule_id' => $req['DeliverySchedule']['id'],
                    'DeliverySchedProduct.status' => 'pending',
            ]]);
            if (!empty(count($delProds))){ 
                array_push($arr, $req);
            }
        }
        
//        pr($arr);exit;
        
        $this->set(compact('arr','status'));
        
        
    }
    
    public function updateDeliveryAgentNote(){
        
        $this->autoRender = false;
        $this->response->type('json');
        $data = $this->request->data; 
        
        $delivery_schedule_id = $data['delivery_schedule_id'];
        $agent_note = $data['agent_note'];
        
        
            $dateToday = date("Y-m-d H:i:s");
        $this->DeliverySchedule->id = $delivery_schedule_id;
        $this->DeliverySchedule->set(array(
            'agent_note' => $agent_note,
            'note_date' => $dateToday,
        ));
        if ($this->DeliverySchedule->save()) {
            echo json_encode($data);
        }
    }

}
