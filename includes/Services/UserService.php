<?php
namespace AICA\Services;

class UserService {
    private $role_hierarchy = [
        'administrator' => 5,
        'editor' => 4,
        'author' => 3,
        'contributor' => 2,
        'subscriber' => 1
    ];

    public function initializeUser($wp_user_id) {
        $aica_user_id = aica_get_user_id($wp_user_id);
        if (!$aica_user_id) {
            $user_data = get_userdata($wp_user_id);
            if ($user_data) {
                $role = $this->getHighestRole($user_data->roles);
                
                $aica_user_id = aica_add_user(
                    $wp_user_id,
                    $user_data->user_login,
                    $user_data->user_email,
                    $role,
                    current_time('mysql')
                );

                if ($aica_user_id) {
                    aica_log('Inicjalizowano użytkownika: ' . $user_data->user_login . ' (ID: ' . $wp_user_id . ')');
                }
            }
        }
        return $aica_user_id;
    }

    public function getHighestRole($roles) {
        $highest_role = 'subscriber';
        $highest_rank = 0;
        
        foreach ($roles as $role) {
            $rank = $this->role_hierarchy[$role] ?? 0;
            if ($rank > $highest_rank) {
                $highest_rank = $rank;
                $highest_role = $role;
            }
        }
        
        return $highest_role;
    }

    public function updateLastLogin($user_id) {
        return aica_update_user_last_login($user_id);
    }

    public function getUser($user_id) {
        return aica_get_user($user_id);
    }

    public function getAllUsers() {
        return aica_get_users();
    }

    public function deleteUser($user_id) {
        return aica_delete_user($user_id);
    }
} 