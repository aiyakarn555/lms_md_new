<?php

class OrgDivisionController extends Controller
{
	/**
	 * @var string the default layout for the views. Defaults to '//layouts/column2', meaning
	 * using two-column layout. See 'protected/views/layouts/column2.php'.
	 */
	public $layout='//layouts/column2';

	/**
	 * @return array action filters
	 */
	public function filters()
	{
		return array(
            'accessControl', // perform access control for CRUD operations
            // 'rights',
        );
	}

    /**
     * Specifies the access control rules.
     * This method is used by the 'accessControl' filter.
     * @return array access control rules
     */
    public function accessRules()
    {
    	return array(
            array('allow',  // allow all users to perform 'index' and 'view' actions
            	'actions' => array('index', 'view'),
            	'users' => array('*'),
            ),
            array('allow',
                // กำหนดสิทธิ์เข้าใช้งาน actionIndex
            	'actions' => AccessControl::check_action(),
                // ได้เฉพาะ group 1 เท่านั่น
            	'expression' => 'AccessControl::check_access()',
            ),
            array('deny',  // deny all users
            	'users' => array('*'),
            ),
        );
    }
	// public function filters()
	// {
	// 	return array(
	// 		'rights',
	// 	);
	// }

	/**
	 * Specifies the access control rules.
	 * This method is used by the 'accessControl' filter.
	 * @return array access control rules
	 */
//	public function accessRules()
//	{
//		return array(
//			array('allow',  // allow all users to perform 'index' and 'view' actions
//				'actions'=>array('index','view'),
//				'users'=>array('*'),
//			),
//			array('allow', // allow authenticated user to perform 'create' and 'update' actions
//				'actions'=>array('create','update'),
//				'users'=>array('@'),
//			),
//			array('allow', // allow admin user to perform 'admin' and 'delete' actions
//				'actions'=>array('admin','delete'),
//				'users'=>array('admin'),
//			),
//			array('deny',  // deny all users
//				'users'=>array('*'),
//			),
//		);
//	}

	/**
	 * Displays a particular model.
	 * @param integer $id the ID of the model to be displayed
	 */
	public function actionView($id)
	{
		$this->render('view',array(
			'model'=>$this->loadModel($id),
		));
	}

	/**
	 * Creates a new model.
	 * If creation is successful, the browser will be redirected to the 'view' page.
	 */
	public function actionCreate()
	{
		$model = new OrgDivision;
		// Uncomment the following line if AJAX validation is needed
		if(isset($_POST['OrgDivision']))
		{
			$Validation =  $this->formValidation($_POST['OrgDivision']['code']);
			if(count($Validation) == 0){
				$model->attributes=$_POST['OrgDivision'];
				$model->active = 'y';

				if($model->save()){

					$criteria = new CDbCriteria;
					$criteria->compare('id',$_POST['OrgDivision']['department_id']);
					$criteria->compare('active','y');
					$OrgDepartment = OrgDepartment::model()->find($criteria);

					$criteria = new CDbCriteria;
					$criteria->compare('id',$OrgDepartment->bu_id);
					$criteria->compare('active','y');
					$OrgBu = OrgBu::model()->find($criteria);

					$criteria = new CDbCriteria;
					$criteria->compare('department_id',$_POST['OrgDivision']['department_id']);
					$criteria->compare('code',$OrgDepartment->code);
					$criteria->compare('active','y');
					$parent = OrgChart::model()->find($criteria);
					
					$OrgChart = new OrgChart;
					$OrgChart->division_id = $model->id;
					$OrgChart->department_id = $_POST['OrgDivision']['department_id'];
					$OrgChart->group_bu_id = $OrgBu->group_bu_id;
					$OrgChart->bu_id = $OrgDepartment->bu_id;
					$OrgChart->code =  $_POST['OrgDivision']['code'];
					$OrgChart->title =  $_POST['OrgDivision']['name'];
					$OrgChart->level =  5;
					$OrgChart->parent_id =  $parent->id;
					$OrgChart->active = 'y';
					$OrgChart->type = 'provide';
					$OrgChart->save();

					if(Yii::app()->user->id){
						Helpers::lib()->getControllerActionId();
					}
					$this->redirect(array('view','id'=>$model->id));
				}
			}else{
				$this->render('create',array(
					'model'=> $model,
					'Validation' => $Validation
				));
			}
			
		}else{

			$this->render('create',array(
				'model'=>$model,
			));
		}

		
	}

	/**
	 * Updates a particular model.
	 * If update is successful, the browser will be redirected to the 'view' page.
	 * @param integer $id the ID of the model to be updated
	 */
	public function actionUpdate($id)
	{
		$model=$this->loadModel($id);
		$old_code = $model->code;
		// Uncomment the following line if AJAX validation is needed
		// $this->performAjaxValidation($model);

		if(isset($_POST['OrgDivision']))
		{	
			$Validation =  $this->formValidation($_POST['OrgDivision']['code'],$id, $old_code);
			if(count($Validation) == 0){
				$model->attributes=$_POST['OrgDivision'];

				if($model->save()){
					$criteria = new CDbCriteria;
					$criteria->compare('id',$_POST['OrgDivision']['department_id']);
					$criteria->compare('active','y');
					$OrgDepartment = OrgDepartment::model()->find($criteria);

					$criteria = new CDbCriteria;
					$criteria->compare('id',$OrgDepartment->bu_id);
					$criteria->compare('active','y');
					$OrgBu = OrgBu::model()->find($criteria);

					$criteria = new CDbCriteria;
					$criteria->compare('department_id',$_POST['OrgDivision']['department_id']);
					$criteria->compare('code',$OrgDepartment->code);
					$criteria->compare('active','y');
					$parent = OrgChart::model()->find($criteria);

					$criteria = new CDbCriteria;
					$criteria->compare('division_id',$model->id);
					$criteria->compare('code',$old_code);
					$criteria->compare('active','y');
					$OrgChart = OrgChart::model()->find($criteria);
					$OrgChart->division_id = $model->id;
					$OrgChart->department_id = $_POST['OrgDivision']['department_id'];
					$OrgChart->bu_id = $OrgDepartment->bu_id;
					$OrgChart->group_bu_id = $OrgBu->group_bu_id;
					$OrgChart->code =  $_POST['OrgDivision']['code'];
					$OrgChart->title =  $_POST['OrgDivision']['name'];
					$OrgChart->level =  5;
					$OrgChart->parent_id =  $parent->id;
					$OrgChart->active = 'y';
					$OrgChart->save();

					$criteria = new CDbCriteria;
					$criteria->compare('division_id',$model->id);
					$criteria->compare('active','y');
					$OrgChartDivisions = OrgChart::model()->findAll($criteria);
					foreach($OrgChartDivisions as $OrgChartDivision){
						$criteria = new CDbCriteria;
						$criteria->compare('id',$OrgChartDivision->id);
						$ChartDivision = OrgChart::model()->find($criteria);
						$ChartDivision->group_bu_id =  $OrgChart->group_bu_id;
						$ChartDivision->bu_id =  $OrgChart->bu_id;
						$ChartDivision->department_id =  $OrgChart->department_id;
						$ChartDivision->save();
					}

					if(Yii::app()->user->id){
						Helpers::lib()->getControllerActionId();
					}
					$this->redirect(array('view','id'=>$model->id));
				}
			}else{
				$this->render('update',array(
					'model'=> $model,
					'Validation' => $Validation
				));
			}
		}else{
			$this->render('update',array(
				'model'=>$model,
			));
		}

		
	}

	/**
	 * Deletes a particular model.
	 * If deletion is successful, the browser will be redirected to the 'admin' page.
	 * @param integer $id the ID of the model to be deleted
	 */
	public function actionDelete($id)
	{
		//$this->loadModel($id)->delete();
		$model = $this->loadModel($id);
		$model->active = 'n';

		$criteria = new CDbCriteria;
		$criteria->compare('division_id',$model->id);
		$criteria->compare('code',$model->code);
		$OrgChart = OrgChart::model()->find($criteria);
		$OrgChart->active = 'n';
		$OrgChart->save();
		

		if($model->save()){
			if(Yii::app()->user->id){
				Helpers::lib()->getControllerActionId();
			}
		}
		// if AJAX request (triggered by deletion via admin grid view), we should not redirect the browser
		if(!isset($_GET['ajax']))
			$this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('admin'));
	}

	public function actionMultiDelete()
	{
		header('Content-type: application/json');
		if(isset($_POST['chk'])) {
			foreach($_POST['chk'] as $val) {
				$this->actionDelete($val);
			}
		}
	}

	/**
	 * Lists all models.
	 */
	public function actionIndex()
	{
		$model=new OrgDivision('search');
		$model->unsetAttributes();  // clear any default values
		if(isset($_GET['OrgDivision']))
			$model->attributes=$_GET['OrgDivision'];

		$this->render('index',array(
			'model'=>$model,
		));
	}

	/**
	 * Manages all models.
	 */
//	public function actionAdmin()
//	{
//		$model=new FaqType('search');
//		$model->unsetAttributes();  // clear any default values
//		if(isset($_GET['FaqType']))
//			$model->attributes=$_GET['FaqType'];
//
//		$this->render('admin',array(
//			'model'=>$model,
//		));
//	}

	/**
	 * Returns the data model based on the primary key given in the GET variable.
	 * If the data model is not found, an HTTP exception will be raised.
	 * @param integer $id the ID of the model to be loaded
	 * @return FaqType the loaded model
	 * @throws CHttpException
	 */
	public function loadModel($id)
	{
		$model=OrgDivision::model()->findByPk($id);
		if($model===null)
			throw new CHttpException(404,'The requested page does not exist.');
		return $model;
	}

	/**
	 * Performs the AJAX validation.
	 * @param FaqType $model the model to be validated
	 */
	protected function formValidation($code, $id = null, $old_code = null)
	{
		$criteria = new CDbCriteria;
		$criteria->compare('code',$old_code);
		$criteria->compare('active','y');
		$criteria->compare('division_id',$id);
		$checkID = OrgChart::model()->find($criteria);


		$criteria = new CDbCriteria;
		$criteria->compare('code',$code);
		$criteria->compare('active','y');
		$criteria->addCondition('id != :id');
		$criteria->params[ ':id' ] = $checkID->id;
		$OrgChart = OrgChart::model()->find($criteria);

		return $OrgChart;
	}

	public function actionSequence() {

    if (isset($_POST['items']) && is_array($_POST['items'])) {
       
            // Get all current target items to retrieve available sortOrders
        $cur_items = FaqType::model()->findAllByPk($_POST['items'], array('order'=>'sortOrder'));
        
            // Check 1 by 1 and update if neccessary

        foreach ($cur_items as $keys => $values) {

            for ($i = 0; $i < count($_POST['items']); $i++) {
                $item = FaqType::model()->findByPk($_POST['items'][$i]);

                if ($item->sortOrder != $cur_items[$i]->sortOrder) {
                    $item->sortOrder = $cur_items[$i]->sortOrder ;
                    $item->save(false);
                } 

                $modellang2 = FaqType::model()->findByAttributes(array('parent_id'=>$_POST['items'][$i])); 
                 // var_dump($modellang2->sortOrder);exit();
                
                if ($modellang2->sortOrder != $cur_items[$i]->sortOrder) {
                    if ($modellang2->parent_id == '') {
                        $items = FaqType::model()->findByPk($_POST['items'][$i]);
                        $items->sortOrder = $cur_items[$i]->sortOrder ;
                        $items->save(false);
                        
                    }
                    if ($modellang2->parent_id != null) {
                        $modellang2->sortOrder = $cur_items[$i]->sortOrder ;
                        $modellang2->save(false);   
                    }
                    
                } 
            }
        }        
    }
}
}
