<?php

/**
 * This is the model class for table "{{course_md}}".
 *
 * The followings are the available columns in table '{{course_md}}':
 * @property integer $id
 * @property string $course_name
 * @property integer $code
 * @property string $active
 * @property string $create_date
 * @property integer $create_by
 * @property string $update_date
 * @property integer $update_by
 * @property integer $course_type
 */
class CourseMd extends CActiveRecord
{
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return '{{course_md}}';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('code, create_by, update_by, course_type', 'numerical', 'integerOnly'=>true),
			array('course_name', 'length', 'max'=>255),
			array('active', 'length', 'max'=>1),
			array('create_date, update_date', 'safe'),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('id, course_name, code, active, create_date, create_by, update_date, update_by, course_type', 'safe', 'on'=>'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'course_name' => 'Course Name',
			'code' => 'Code',
			'active' => 'Active',
			'create_date' => 'Create Date',
			'create_by' => 'Create By',
			'update_date' => 'Update Date',
			'update_by' => 'Update By',
			'course_type' => 'Course Type',
		);
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 *
	 * Typical usecase:
	 * - Initialize the model fields with values from filter form.
	 * - Execute this method to get CActiveDataProvider instance which will filter
	 * models according to data in model fields.
	 * - Pass data provider to CGridView, CListView or any similar widget.
	 *
	 * @return CActiveDataProvider the data provider that can return the models
	 * based on the search/filter conditions.
	 */
	public function search()
	{
		// @todo Please modify the following code to remove attributes that should not be searched.

		$criteria=new CDbCriteria;

		$criteria->compare('id',$this->id);
		$criteria->compare('course_name',$this->course_name,true);
		$criteria->compare('code',$this->code);
		$criteria->compare('active',$this->active,true);
		$criteria->compare('create_date',$this->create_date,true);
		$criteria->compare('create_by',$this->create_by);
		$criteria->compare('update_date',$this->update_date,true);
		$criteria->compare('update_by',$this->update_by);
		$criteria->compare('course_type',$this->course_type);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return CourseMd the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
}
