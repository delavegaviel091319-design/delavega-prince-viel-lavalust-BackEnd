<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * User_model - accounts used by the authentication system (table: users).
 */
class User_model extends Model
{
    protected $table = 'users';

    public function find_by_login($login)
    {
        return $this->db->table($this->table)
                        ->where('username', $login)
                        ->or_where('email', $login)
                        ->get();
    }

    public function find_by_id($id)
    {
        return $this->db->table($this->table)
                        ->where('id', (int) $id)
                        ->get();
    }

    public function username_or_email_taken($username, $email)
    {
        return (bool) $this->db->table($this->table)
                        ->where('username', $username)
                        ->or_where('email', $email)
                        ->get();
    }

    public function create_user($username, $email, $password_hash, $role = 'user')
    {
        $this->db->table($this->table)->insert([
            'username'   => $username,
            'email'      => $email,
            'password'   => $password_hash,
            'role'       => $role,
            'is_active'  => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        return (int) $this->db->last_id();
    }
}
