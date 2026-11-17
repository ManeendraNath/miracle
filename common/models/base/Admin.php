<?php

namespace common\models\base;

use Yii;

/**
 * This is the model class for table "admin".
 *
 * @property int $id
 * @property string $username
 * @property string $email
 * @property string|null $display_name
 * @property string|null $bio
 * @property string|null $photo
 * @property string $auth_key
 * @property string $password_hash
 * @property string|null $password_reset_token
 * @property string|null $totp_secret
 * @property int|null $totp_last_step
 * @property string|null $totp_backup_codes
 * @property string $role
 * @property int $status
 * @property int $created_at
 * @property int $updated_at
 *
 * @property BlogPostRevision[] $blogPostRevisions
 * @property BlogPost[] $blogPosts
 */
class Admin extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'admin';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['display_name', 'bio', 'photo', 'password_reset_token', 'totp_secret', 'totp_last_step', 'totp_backup_codes'], 'default', 'value' => null],
            [['role'], 'default', 'value' => 'Manager'],
            [['status'], 'default', 'value' => 1],
            [['username', 'email', 'auth_key', 'password_hash', 'created_at', 'updated_at'], 'required'],
            [['totp_last_step', 'status', 'created_at', 'updated_at'], 'integer'],
            [['totp_backup_codes'], 'string'],
            [['username', 'email', 'photo', 'password_hash', 'password_reset_token', 'totp_secret'], 'string', 'max' => 255],
            [['display_name'], 'string', 'max' => 100],
            [['bio'], 'string', 'max' => 500],
            [['auth_key'], 'string', 'max' => 32],
            [['role'], 'string', 'max' => 50],
            [['username'], 'unique'],
            [['email'], 'unique'],
            [['password_reset_token'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'username' => 'Username',
            'email' => 'Email',
            'display_name' => 'Display Name',
            'bio' => 'Bio',
            'photo' => 'Photo',
            'auth_key' => 'Auth Key',
            'password_hash' => 'Password Hash',
            'password_reset_token' => 'Password Reset Token',
            'totp_secret' => 'Totp Secret',
            'totp_last_step' => 'Totp Last Step',
            'totp_backup_codes' => 'Totp Backup Codes',
            'role' => 'Role',
            'status' => 'Status',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * Gets query for [[BlogPostRevisions]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getBlogPostRevisions()
    {
        return $this->hasMany(BlogPostRevision::class, ['author_id' => 'id']);
    }

    /**
     * Gets query for [[BlogPosts]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getBlogPosts()
    {
        return $this->hasMany(BlogPost::class, ['author_id' => 'id']);
    }
}
