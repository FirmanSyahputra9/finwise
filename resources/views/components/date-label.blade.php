@props(['date']) @php($label = $date->isToday() ? __('ui.today') : ($date->isYesterday() ? __('ui.yesterday') : $date->translatedFormat('d M Y'))){{ $label }}
