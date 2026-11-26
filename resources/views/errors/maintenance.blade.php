<x-error-page code="503" :title="__('ui.error_503_title')" :message="$maintenanceMessage ?? __('ui.error_503_message')" :retry-after="$retryAfter ?? null" />
