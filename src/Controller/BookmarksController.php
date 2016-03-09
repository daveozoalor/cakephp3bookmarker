<?php
namespace App\Controller;

use App\Controller\AppController;

/**
 * Bookmarks Controller
 *
 * @property \App\Model\Table\BookmarksTable $Bookmarks
 */
class BookmarksController extends AppController
{

public function beforeSave($event, $entity, $options)
{
    if ($entity->tag_string) {
        $entity->tags = $this->_buildTags($entity->tag_string);
    }
}


public function isAuthorized($user)
{
    $action = $this->request->params['action'];

    // The add and index actions are always allowed.
    if (in_array($action, ['index', 'add', 'tags'])) {
        return true;
    }
    // All other actions require an id.
    if (empty($this->request->params['pass'][0])) {
        return false;
    }

    // Check that the bookmark belongs to the current user.
    $id = $this->request->params['pass'][0];
    $bookmark = $this->Bookmarks->get($id);
    if ($bookmark->user_id == $user['id']) {
        return true;
    }
    return parent::isAuthorized($user);
}


protected function _buildTags($tagString)
{
    $new = array_unique(array_map('trim', explode(',', $tagString)));
    $out = [];
    $query = $this->Tags->find()->where(['Tags.title IN' => $new]);

    // Remove existing tags from the list of new tags.
    foreach ($query->extract('title') as $existing) {
        $index = array_search($existing, $new);
        if ($index !== false) {
            unset($new[$index]);
        }
    }
    // Add existing tags.
    foreach ($query as $tag) {
        $out[] = $tag;
    }
    // Add new tags.
    foreach ($new as $tag) {
        $out[] = $this->Tags->newEntity(['title' => $tag]);
    }
    return $out;
}
    /**
     * Index method
     *
     * @return void
     */
    public function index()
    {
        $this->paginate = [
        'conditions' => [
            'Bookmarks.user_id' => $this->Auth->user('id'),
        ]
    ];
    $this->set('bookmarks', $this->paginate($this->Bookmarks));
    $this->set('_serialize', ['bookmarks']);
    }

	public function tags()
	{
		// The 'pass' key is provided by CakePHP and contains all
		// the passed URL path segments in the request.
		$tags = $this->request->params['pass'];
		// Use the BookmarksTable to find tagged bookmarks.
		$bookmarks = $this->Bookmarks->find('tagged', ['tags' => $tags]);
		// Pass variables into the view template context.
		$this->set([
		'bookmarks' => $bookmarks,
		'tags' => $tags
		]);
  }
    /**
     * View method
     *
     * @param string|null $id Bookmark id.
     * @return void
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function view($id = null)
    {
        $bookmark = $this->Bookmarks->get($id, [
            'contain' => ['Users', 'Tags']
        ]);
        $this->set('bookmark', $bookmark);
        $this->set('_serialize', ['bookmark']);
    }

    /**
     * Add method
     *
     * @return void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $bookmark = $this->Bookmarks->newEntity();
        if ($this->request->is('post')) {
            $bookmark = $this->Bookmarks->patchEntity($bookmark, $this->request->data);
            $bookmark->user_id = $this->Auth->user('id');
            if ($this->Bookmarks->save($bookmark)) {
                $this->Flash->success(__('The bookmark has been saved.'));
                return $this->redirect(['action' => 'index']);
            } else {
                $this->Flash->error(__('The bookmark could not be saved. Please, try again.'));
            }
        }
        $users = $this->Bookmarks->Users->find('list', ['limit' => 200]);
        $tags = $this->Bookmarks->Tags->find('list', ['limit' => 200]);
        $this->set(compact('bookmark', 'users', 'tags'));
        $this->set('_serialize', ['bookmark']);
    }

    /**
     * Edit method
     *
     * @param string|null $id Bookmark id.
     * @return void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $bookmark = $this->Bookmarks->get($id, [
            'contain' => ['Tags']
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $bookmark = $this->Bookmarks->patchEntity($bookmark, $this->request->data);
            $bookmark->user_id = $this->Auth->user('id');
            if ($this->Bookmarks->save($bookmark)) {
                $this->Flash->success(__('The bookmark has been saved.'));
                return $this->redirect(['action' => 'index']);
            } else {
                $this->Flash->error(__('The bookmark could not be saved. Please, try again.'));
            }
        }
        $users = $this->Bookmarks->Users->find('list', ['limit' => 200]);
        $tags = $this->Bookmarks->Tags->find('list', ['limit' => 200]);
        $this->set(compact('bookmark', 'users', 'tags'));
        $this->set('_serialize', ['bookmark']);
    }

    /**
     * Delete method
     *
     * @param string|null $id Bookmark id.
     * @return \Cake\Network\Response|null Redirects to index.
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $bookmark = $this->Bookmarks->get($id);
        if ($this->Bookmarks->delete($bookmark)) {
            $this->Flash->success(__('The bookmark has been deleted.'));
        } else {
            $this->Flash->error(__('The bookmark could not be deleted. Please, try again.'));
        }
        return $this->redirect(['action' => 'index']);
    }
}
