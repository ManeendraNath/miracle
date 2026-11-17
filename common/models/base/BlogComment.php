<?php

namespace common\models\base;

use Yii;

/**
 * This is the model class for table "blog_comment".
 *
 * @property int $id
 * @property int $post_id
 * @property int|null $parent_id
 * @property int|null $admin_id
 * @property string $author_name
 * @property string|null $author_email
 * @property string $body
 * @property int $status
 * @property string|null $ip
 * @property int $created_at
 * @property int $updated_at
 *
 * @property Admin $admin
 * @property BlogComment[] $blogComments
 * @property BlogComment $parent
 * @property BlogPost $post
 */
class BlogComment extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'blog_comment';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['parent_id', 'admin_id', 'author_email', 'ip'], 'default', 'value' => null],
            [['status'], 'default', 'value' => 0],
            [['post_id', 'author_name', 'body', 'created_at', 'updated_at'], 'required'],
            [['post_id', 'parent_id', 'admin_id', 'status', 'created_at', 'updated_at'], 'integer'],
            [['body'], 'string'],
            [['author_name'], 'string', 'max' => 100],
            [['author_email'], 'string', 'max' => 255],
            [['ip'], 'string', 'max' => 45],
            [['admin_id'], 'exist', 'skipOnError' => true, 'targetClass' => Admin::class, 'targetAttribute' => ['admin_id' => 'id']],
            [['parent_id'], 'exist', 'skipOnError' => true, 'targetClass' => BlogComment::class, 'targetAttribute' => ['parent_id' => 'id']],
            [['post_id'], 'exist', 'skipOnError' => true, 'targetClass' => BlogPost::class, 'targetAttribute' => ['post_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'post_id' => 'Post ID',
            'parent_id' => 'Parent ID',
            'admin_id' => 'Admin ID',
            'author_name' => 'Author Name',
            'author_email' => 'Author Email',
            'body' => 'Body',
            'status' => 'Status',
            'ip' => 'Ip',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * Gets query for [[Admin]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAdmin()
    {
        return $this->hasOne(Admin::class, ['id' => 'admin_id']);
    }

    /**
     * Gets query for [[BlogComments]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getBlogComments()
    {
        return $this->hasMany(BlogComment::class, ['parent_id' => 'id']);
    }

    /**
     * Gets query for [[Parent]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getParent()
    {
        return $this->hasOne(BlogComment::class, ['id' => 'parent_id']);
    }

    /**
     * Gets query for [[Post]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPost()
    {
        return $this->hasOne(BlogPost::class, ['id' => 'post_id']);
    }
}
