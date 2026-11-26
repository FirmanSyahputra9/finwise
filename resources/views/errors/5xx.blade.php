<x-error-page :code="$exception->getStatusCode()" :title="__('ui.error_server_title')" :message="__('ui.error_server_message')" />
