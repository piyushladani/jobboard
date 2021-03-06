<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Event\Event;
use Cake\Mailer\Email;
use Cake\Mailer\MailerAwareTrait;
use Cake\Routing\Router;
use Cake\Utility\Security;
use App\Controller;
use Cake\ORM\TableRegistry;

/**
 * Jobs Controller
 *
 * @property \App\Model\Table\JobsTable $Jobs
 *
 * @method \App\Model\Entity\Job[] paginate($object = null, array $settings = [])
 */
class JobsController extends AppController
{

    /**
     * Index method
     *
     * @return \Cake\Http\Response|void
     */
    public function index()
    {
        $this->paginate = [
            'contain' => ['Users']
        ];
        $jobs = $this->paginate($this->Jobs);

        $this->set(compact('jobs'));
        $this->set('_serialize', ['jobs']);
    }

    /**
     * View method
     *
     * @param string|null $id Job id.
     * @return \Cake\Http\Response|void
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $job = $this->Jobs->get($id, [
            'contain' => ['Users']
        ]);

        

        $this->set('job', $job);
        $this->set('_serialize', ['job']);
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $job = $this->Jobs->newEntity();
        //$user=$this->Users->newEntity();
        if ($this->request->is('post')) {
            $job = $this->Jobs->patchEntity($job, $this->request->getData());
            if ($this->Jobs->save($job)) {
                $this->Flash->success(__('The post has been saved.'));

                //Retrieve the corresponding user email
                $uid=$job->user_id;
                $user = TableRegistry::get('Users');
                $user = $user
                ->find()
                ->where(['id' => $uid])
                ->first();
                $mail=(string)$user->email;

                //preparing token and sending it via an E-mail
                $token = uniqid();
                $key  = Security::hash('CakePHP Framework', 'sha1', true);
                $msg=Router::url( array('controller'=>'jobs','action'=>'editjob'), true).'/'.$token;
                if ($this->Jobs->updateAll(['token' => $token], ['id' => $job->id])){
                $email = new Email();
                $email->from(['piyush_ladani@gmx.net' => 'Piyush Ladani'])
                ->to($mail)
                ->subject('Welcome to Jobboard')
                ->send("Dear valued customer,\n\nThanks for signing up with us!\nYou may change/edit your posted job at any time by signing on to your home page and using the “Edit Profile” link below:\n\n".$msg."\n\nRegards,\nJobboard Team");

                } else {
                    $this->Flash->error('Error saving reset passkey/timeout');
                }

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The post could not be saved. Please, try again.'));
        }
        $users = $this->Jobs->Users->find('list', ['limit' => 200]);
        $this->set(compact('job', 'users'));
        $this->set('_serialize', ['job']);
    }

    /**
     * Edit method
     *
     * @param string|null $id Job id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $job = $this->Jobs->get($id, [
            'contain' => []
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $job = $this->Jobs->patchEntity($job, $this->request->getData());
            if ($this->Jobs->save($job)) {
                $this->Flash->success(__('The job has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The job could not be saved. Please, try again.'));
        }
        $users = $this->Jobs->Users->find('list', ['limit' => 200]);
        $this->set(compact('job', 'users'));
        $this->set('_serialize', ['job']);
    }

    /**
     * Delete method
     *
     * @param string|null $id Job id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $job = $this->Jobs->get($id);
        if ($this->Jobs->delete($job)) {
            $this->Flash->success(__('The job has been deleted.'));
        } else {
            $this->Flash->error(__('The job could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    public function editjob($token){
        //this function is used to edit the job he has newly posted when the user click the link sent via E--mail
        $job = TableRegistry::get('Jobs');
        $job = $job
        ->find()
        ->where(['token' => $token])
        ->first();

        $job = $this->Jobs->get($job->id, [
            'contain' => []
        ]);
        $this->request->data['passkey'] = null; 

       if ($this->request->is(['patch', 'post', 'put'])) {
            $job = $this->jobs->patchEntity($job, $this->request->getData());
            if ($this->Jobs->save($job)) {
                $this->Flash->success(__('The post has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The post could not be saved. Please, try again.'));
        } 
       $users = $this->Jobs->Users->find('list', ['limit' => 200]);
                    $this->set(compact('job', 'users'));
                    $this->set('_serialize', ['job']);
    }
}
