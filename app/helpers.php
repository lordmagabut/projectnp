<?php
  
function active_class($path, $active = 'active') {
  return call_user_func_array('Request::is', (array)$path) ? $active : '';
}

function is_active_route($path) {
  return call_user_func_array('Request::is', (array)$path) ? 'true' : 'false';
}

function show_class($path) {
  return call_user_func_array('Request::is', (array)$path) ? 'show' : '';
}

/**
 * Return logo URL for a company or fallback default image.
 * Accepts either a model with `logo_path` or a raw path string.
 */
function company_logo_url($company = null)
{
  $path = null;
  if (is_string($company)) {
    $path = $company;
  } elseif (is_object($company) && isset($company->logo_path)) {
    $path = $company->logo_path;
  }

  if ($path && file_exists(public_path('storage/' . $path))) {
    return asset('storage/' . $path);
  }

  return asset('images/default-logo.svg');
}