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

/**
 * Return favicon URL derived from company logo. Converts common raster types
 * (jpg/jpeg/gif/webp) to PNG automatically and reuses existing PNG/ICO/SVG.
 * Stores converted PNG under public/storage/favicons/{basename}.png
 */
function company_favicon_url($company = null)
{
  $path = null;
  if (is_string($company)) {
    $path = $company;
  } elseif (is_object($company) && isset($company->logo_path)) {
    $path = $company->logo_path;
  }

  $srcAbs = $path ? public_path('storage/' . $path) : null;
  if (!$srcAbs || !file_exists($srcAbs)) {
    // fallback default
    return asset('images/default-logo.svg');
  }

  $ext = strtolower(pathinfo($srcAbs, PATHINFO_EXTENSION));
  // If already favicon-friendly, just return it
  if (in_array($ext, ['png', 'ico', 'svg'])) {
    return asset('storage/' . $path);
  }

  // Prepare target path
  $baseName = pathinfo($srcAbs, PATHINFO_FILENAME);
  $targetRel = 'favicons/' . $baseName . '.png';
  $targetAbs = public_path('storage/' . $targetRel);

  // Ensure directory exists
  $dir = dirname($targetAbs);
  if (!is_dir($dir)) {
    @mkdir($dir, 0755, true);
  }

  // If already converted, reuse
  if (file_exists($targetAbs)) {
    return asset('storage/' . $targetRel);
  }

  // Try conversion using GD for raster formats
  $img = null;
  switch ($ext) {
    case 'jpg':
    case 'jpeg':
      if (function_exists('imagecreatefromjpeg')) {
        $img = @imagecreatefromjpeg($srcAbs);
      }
      break;
    case 'gif':
      if (function_exists('imagecreatefromgif')) {
        $img = @imagecreatefromgif($srcAbs);
      }
      break;
    case 'webp':
      if (function_exists('imagecreatefromwebp')) {
        $img = @imagecreatefromwebp($srcAbs);
      }
      break;
    default:
      // Unknown raster type; bail out to original
      return asset('storage/' . $path);
  }

  if (!$img) {
    // Fallback
    return asset('storage/' . $path);
  }

  // Normalize to truecolor PNG, preserving alpha if present
  $width = imagesx($img);
  $height = imagesy($img);
  $out = imagecreatetruecolor($width, $height);
  imagealphablending($out, false);
  imagesavealpha($out, true);
  $transparent = imagecolorallocatealpha($out, 0, 0, 0, 127);
  imagefilledrectangle($out, 0, 0, $width, $height, $transparent);
  imagecopy($out, $img, 0, 0, 0, 0, $width, $height);

  @imagepng($out, $targetAbs, 6);
  imagedestroy($out);
  imagedestroy($img);

  if (file_exists($targetAbs)) {
    return asset('storage/' . $targetRel);
  }

  // As a last resort, return original
  return asset('storage/' . $path);
}