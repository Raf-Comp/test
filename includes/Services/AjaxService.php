<?php
declare(strict_types=1);

namespace AICA\Services;

use AICA\Helpers\AjaxHelper;

class AjaxService {
    public function __construct(
        private readonly AjaxHelper $ajax_helper
    ) {}

    public function is_ajax_request(): bool {
        return $this->ajax_helper->is_ajax_request();
    }

    public function is_rest_request(): bool {
        return $this->ajax_helper->is_rest_request();
    }

    public function is_cli_request(): bool {
        return $this->ajax_helper->is_cli_request();
    }

    public function is_cron_request(): bool {
        return $this->ajax_helper->is_cron_request();
    }

    public function is_xmlrpc_request(): bool {
        return $this->ajax_helper->is_xmlrpc_request();
    }

    public function is_api_request(): bool {
        return $this->ajax_helper->is_api_request();
    }

    public function is_admin_request(): bool {
        return $this->ajax_helper->is_admin_request();
    }

    public function is_frontend_request(): bool {
        return $this->ajax_helper->is_frontend_request();
    }

    public function is_login_request(): bool {
        return $this->ajax_helper->is_login_request();
    }

    public function is_install_request(): bool {
        return $this->ajax_helper->is_install_request();
    }

    public function is_activation_request(): bool {
        return $this->ajax_helper->is_activation_request();
    }

    public function is_deactivation_request(): bool {
        return $this->ajax_helper->is_deactivation_request();
    }

    public function is_uninstall_request(): bool {
        return $this->ajax_helper->is_uninstall_request();
    }

    public function is_update_request(): bool {
        return $this->ajax_helper->is_update_request();
    }

    public function is_installation_request(): bool {
        return $this->ajax_helper->is_installation_request();
    }

    public function is_deinstallation_request(): bool {
        return $this->ajax_helper->is_deinstallation_request();
    }

    public function is_system_request(): bool {
        return $this->ajax_helper->is_system_request();
    }

    public function is_user_request(): bool {
        return $this->ajax_helper->is_user_request();
    }
} 