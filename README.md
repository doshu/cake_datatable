# Datatable plugin for CakePHP

## Installation

You can install this plugin into your CakePHP application using [composer](https://getcomposer.org).

The recommended way to install composer packages is:

```
composer require doshu/datatable
```

The plugin is intended to be used in a standard cakephp 4.x application
The tables are rendered using vue js 2, and generated with a class based configuration

## Load the Datatable Helper

```
$this->loadHelper('Datatable.Datatable')
```

## Insert static files in the layout
use the Datatable Helper fol loading static assets in the layout file
create a global js variable named BASE_UR with the application base url and a _CSRF_TOKEN variable with the CSRF TOKEN

```
<head>
  <?= $this->Datatable->loadAssets(); ?>
  <script type="text/javascript">
    var BASE_URL = "<?= \Cake\Routing\Router::fullBaseUrl().$this->request->getUri()->base; ?>";
    var _CSRF_TOKEN = "<?= $this->request->getAttribute('csrfToken') ?>";
  </script>
</head>
```

## Define a datatable backend

create a class in APP/src/Model/Datatable (the location can be changed)
in this example we will create a datatable for showing all clients

```
<?php

    namespace App\Model\Datatable;

    class ClientsDatatable extends \Datatable\Model\TableSchema
    {
        /*
         * _default order set the default used in the datatable
         */
        protected $_defaultOrder = ['column' => 'name', 'direction' => 'asc'];
        
        /*
         * the _prepareColumns function is called initially for defining
         * columns type, label, options, ecc..
         */
        protected function  _prepareColumns() {
        
            $this->_addColumn('name', [
                'header'=> 'Nome',
            ]);
            
            $this->_addColumn('phone', [
                'header'=> 'Telefono',
            ]);
            
            $this->_addColumn('email', [
                'header'=> 'Email',
            ]);
        }
        
        /*
         * this function is called for every row, 
         * and is used to define the available row actions
         */
        protected function _prepareRowsAction($row) 
        {
            return [
                'edit' => [
                    'title' => 'Modifica',
                    'type' => 'get',
                    'url' => ['controller' => 'clients', 'action' => 'edit', $row->id],
                ],
                'delete' => [
                    'title' => 'Elimina',
                    'type' => 'post',
                    'url' => ['controller' => 'clients', 'action' => 'delete', $row->id],
                    'confirm' => 'Sei sicuro di voler eliminare il cliente?'
                ],
            ];
        }

    }


?>
```

see the example folder for all the available optiuons

## Create the controller actions

after defining the datatable model class, you can create the controller actions that will render the datatable and that will send the datat to the frontend

```
public function index()
{
    $datatable = new \App\Model\Datatable\ClientsDatatable(
        ['controller' => 'Clients', 'action' => 'datatable', '_ext' => 'json'], //set the action that the datatable will send requests to
        $this //controller instance
    );

    $this->set(compact('datatable'));
}

public function datatable() {
    //create the datatable instance in the same way as in the index action
    $datatable = new \App\Model\Datatable\ClientsDatatable(
        ['controller' => 'Clients', 'action' => 'datatable', '_ext' => 'json'],
        $this
    );
    //create the initial query and pass to the prepareCollection datatable method
    $clients = $this->Clients->find();
    //prepareCollection will apply all the filter and sorting to the collection and will set the result body to the response
    $datatable->prepareCollection($clients);
}
```

## Render the datatable
use the datatable helper to rendere the datatable wherever you want in the view file

```
<div class="content">
  <?= $this->Datatable->display($datatable); ?>
</div>
```
