<?php

class TypeNews extends AActiveRecord
{
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	public function tableName()
	{
		return '{{type_news}}';
	}

	public function rules()
	{
		return array(
			array('create_by, update_by', 'numerical', 'integerOnly'=>true),
			array('cms_type_title', 'length', 'max'=>250),
			// array('cms_type_picture', 'length', 'max'=>200, 'on'=>'insert,update'),
			// array('cms_type_picture', 'file','types' => 'jpg, gif, png', 'allowEmpty'=>true),
			array('active', 'length', 'max'=>1),
			array('cms_type_title', 'required'),
			array(' cms_type_detail, create_date, update_date ,news_per_page, lang_id,parent_id', 'safe'),
			array('cms_type_id, cms_type_title, create_date, create_by, update_date, update_by, active, lang_id,parent_id', 'safe', 'on'=>'search'),
		);
	}

	public function relations()
	{
		return array(
			'usercreate' => array(self::BELONGS_TO, 'User', 'create_by'),
			'userupdate' => array(self::BELONGS_TO, 'User', 'update_by'),
		);
	}

	public function attributeLabels()
	{
		
		return array(
			'cms_type_id' => 'ID',
			'cms_type_title' => 'title',
			'create_date' => 'create_date',
			'create_by' => 'create_by',
			'update_date' => 'update_date',
			'update_by' => 'update_by',
			'active' => 'status',
			'parent_id' => 'menu',
			'lang_id' => 'anguage',
		);
	}

	public function search()
	{
		$criteria = new CDbCriteria;
		$criteria->compare('cms_type_id',$this->cms_type_id);
		$criteria->compare('cms_type_title',$this->cms_type_title,true);
		$criteria->compare('create_date',$this->create_date,true);
		$criteria->compare('create_by',$this->create_by);
		$criteria->compare('update_date',$this->update_date,true);
		$criteria->compare('update_by',$this->update_by);
		$criteria->compare('active',$this->active,true);
		$criteria->compare('parent_id',0);
		$criteria->order = 'sortOrder ASC';

		$poviderArray = array('criteria'=>$criteria);

		// Page
		if(isset($this->news_per_page))
		{
			$poviderArray['pagination'] = array( 'pageSize'=> intval($this->news_per_page) );
		}

		return new CActiveDataProvider($this, $poviderArray);
	}

   	public function beforeSave()
    {
    	$this->cms_type_title = CHtml::encode($this->cms_type_title);

		if(null !== Yii::app()->user && isset(Yii::app()->user->id))
		{
			$id = Yii::app()->user->id;
		}
		else
		{
			$id = 0;
		}

		if($this->isNewRecord)
		{
			$this->create_by = $id;
			$this->create_date = date("Y-m-d H:i:s");
			$this->update_by = $id;
			$this->update_date = date("Y-m-d H:i:s");
		}
		else
		{
			$this->update_by = $id;
			$this->update_date = date("Y-m-d H:i:s");
		}

        return parent::beforeSave();
    }

    public function afterFind()
    {
    	$this->cms_type_title = CHtml::decode($this->cms_type_title);

        return parent::afterFind();
    }

    public function checkScopes($check = 'scopes')
    {
    	if ($check == 'scopes')
    	{
		    $checkScopes =  array(
		    	'alias' => 'typenews',
		    	'order' => 'update_date ASC ',
		    	'condition' => 'active = "y" ',
		    );
    	}
    	else
    	{
		    $checkScopes =  array(
		    	'alias' => 'typenews',
		    	'order' => 'update_date ASC ',
		    );
    	}

		return $checkScopes;
    }

	public function scopes()
    {
    	//========== SET Controller loadModel() ==========//

		$Access = Controller::SetAccess( array("TypeNews.*") );
		$user = User::model()->findByPk(Yii::app()->user->id);
		$state = Helpers::lib()->getStatePermission($user);

		if($Access == true)
		{
			$scopes =  array(
				'typenewscheck' => $this->checkScopes('scopes')
			);
		}
		else
		{
			if(isset(Yii::app()->user->isSuperuser) && Yii::app()->user->isSuperuser == true)
			{
				$scopes =  array(
					'typenewscheck' => $this->checkScopes('scopes')
				);
			}
			else
			{
				if($state){
					$scopes = array(
						'typenewscheck'=>array(
							'alias' => 'typenews',
							'order' => 'update_date ASC ',
							'condition' => 'active = "y" ',
						),
					);
				}else{
					$scopes = array(
						'typenewscheck'=>array(
							'alias' => 'news',
							'order' => 'update_date ASC ',
							'condition' => 'active = "y" ',
							// 'condition' => ' typenews.create_by = "'.Yii::app()->user->id.'" AND news.active = "y" ',
						),
					);
				}
			    
			    // $scopes = array(
		     //        'typenewscheck'=>array(
			    // 		'alias' => 'news',
			    // 		'order' => ' news.cms_type_id DESC ',
			    // 		'condition' => ' news.active = "y" ',
		     //        ),
			    // );
			}
		}

		return $scopes;
    }

	public function defaultScope()
	{
	    $defaultScope =  $this->checkScopes('defaultScope');

		return $defaultScope;
	}

	public function getId()
	{
		return $this->cms_type_id;
	}
}
