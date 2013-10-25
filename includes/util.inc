<?php
/**
 * @file
 * Basic utility functions.
 */

/**
 * Encodes special characters in a plain-text string for display as HTML.
 * Also validates strings as UTF-8 to prevent cross site scripting attacks on
 * Internet Explorer 6.
 *
 * Borrowed from Drupal.
 * http://api.drupal.org/api/drupal/core%21includes%21bootstrap.inc/function/check_plain/8
 */
function check_plain($text) {
  return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * Filters HTML to prevent cross-site-scripting (XSS) vulnerabilities.
 * This code does four things:
 *  - Removes characters and constructs that can trick browsers.
 *  - Makes sure all HTML entities are well-formed.
 *  - Makes sure all HTML tags and attributes are well-formed.
 *  - Makes sure no HTML tags contain URLs with a disallowed protocol
 *    (e.g. javascript:).
 *
 * Borrowed from Drupal.
 * http://api.drupal.org/api/drupal/core%21includes%21common.inc/function/filter_xss/8
 */
function filter_xss($string, $allowed_tags = array('a', 'em', 'strong', 'cite', 'blockquote', 'code', 'ul', 'ol', 'li', 'dl', 'dt', 'dd')) {
  // Only operate on valid UTF-8 strings. This is necessary to prevent cross
  // site scripting issues on Internet Explorer 6.
  if (!perseus_validate_utf8($string)) {
    return '';
  }
  // Store the text format.
  _filter_xss_split($allowed_tags, TRUE);
  // Remove NULL characters (ignored by some browsers).
  $string = str_replace(chr(0), '', $string);
  // Remove Netscape 4 JS entities.
  $string = preg_replace('%&\s*\{[^}]*(\}\s*;?|$)%', '', $string);

  // Defuse all HTML entities.
  $string = str_replace('&', '&amp;', $string);
  // Change back only well-formed entities in our whitelist:
  // Decimal numeric entities.
  $string = preg_replace('/&amp;#([0-9]+;)/', '&#\1', $string);
  // Hexadecimal numeric entities.
  $string = preg_replace('/&amp;#[Xx]0*((?:[0-9A-Fa-f]{2})+;)/', '&#x\1', $string);
  // Named entities.
  $string = preg_replace('/&amp;([A-Za-z][A-Za-z0-9]*;)/', '&\1', $string);

  return preg_replace_callback('%
    (
    <(?=[^a-zA-Z!/])  # a lone <
    |                 # or
    <!--.*?-->        # a comment
    |                 # or
    <[^>]*(>|$)       # a string that starts with a <, up until the > or the end of the string
    |                 # or
    >                 # just a >
    )%x', '_filter_xss_split', $string);
}

/**
 * Processes an HTML tag.
 *
 * Borrowed from Drupal.
 * http://api.drupal.org/api/drupal/core%21includes%21common.inc/function/_filter_xss_split/8
 */
function _filter_xss_split($m, $store = FALSE) {
  static $allowed_html;

  if ($store) {
    $allowed_html = array_flip($m);
    return;
  }

  $string = $m[1];

  if (substr($string, 0, 1) != '<') {
    // We matched a lone ">" character.
    return '&gt;';
  }
  elseif (strlen($string) == 1) {
    // We matched a lone "<" character.
    return '&lt;';
  }

  if (!preg_match('%^<\s*(/\s*)?([a-zA-Z0-9]+)([^>]*)>?|(<!--.*?-->)$%', $string, $matches)) {
    // Seriously malformed.
    return '';
  }

  $slash = trim($matches[1]);
  $elem = &$matches[2];
  $attrlist = &$matches[3];
  $comment = &$matches[4];

  if ($comment) {
    $elem = '!--';
  }

  if (!isset($allowed_html[strtolower($elem)])) {
    // Disallowed HTML element.
    return '';
  }

  if ($comment) {
    return $comment;
  }

  if ($slash != '') {
    return "</$elem>";
  }

  // Is there a closing XHTML slash at the end of the attributes?
  $attrlist = preg_replace('%(\s?)/\s*$%', '\1', $attrlist, -1, $count);
  $xhtml_slash = $count ? ' /' : '';

  // Clean up attributes.
  $attr2 = implode(' ', _filter_xss_attributes($attrlist));
  $attr2 = preg_replace('/[<>]/', '', $attr2);
  $attr2 = strlen($attr2) ? ' ' . $attr2 : '';

  return "<$elem$attr2$xhtml_slash>";
}

/**
 * Processes a string of HTML attributes.
 *
 * Borrowed from Drupal.
 * http://api.drupal.org/api/drupal/core%21includes%21common.inc/function/_filter_xss_attributes/8
 */
function _filter_xss_attributes($attr) {
  $attrarr = array();
  $mode = 0;
  $attrname = '';

  while (strlen($attr) != 0) {
    // Was the last operation successful?
    $working = 0;

    switch ($mode) {
      case 0:
        // Attribute name, href for instance.
        if (preg_match('/^([-a-zA-Z]+)/', $attr, $match)) {
          $attrname = strtolower($match[1]);
          $skip = ($attrname == 'style' || substr($attrname, 0, 2) == 'on');
          $working = $mode = 1;
          $attr = preg_replace('/^[-a-zA-Z]+/', '', $attr);
        }
        break;

      case 1:
        // Equals sign or valueless ("selected").
        if (preg_match('/^\s*=\s*/', $attr)) {
          $working = 1;
          $mode = 2;
          $attr = preg_replace('/^\s*=\s*/', '', $attr);
          break;
        }

        if (preg_match('/^\s+/', $attr)) {
          $working = 1;
          $mode = 0;
          if (!$skip) {
            $attrarr[] = $attrname;
          }
          $attr = preg_replace('/^\s+/', '', $attr);
        }
        break;

      case 2:
        // Attribute value, a URL after href= for instance.
        if (preg_match('/^"([^"]*)"(\s+|$)/', $attr, $match)) {
          $thisval = filter_xss_bad_protocol($match[1]);

          if (!$skip) {
            $attrarr[] = "$attrname=\"$thisval\"";
          }
          $working = 1;
          $mode = 0;
          $attr = preg_replace('/^"[^"]*"(\s+|$)/', '', $attr);
          break;
        }

        if (preg_match("/^'([^']*)'(\s+|$)/", $attr, $match)) {
          $thisval = filter_xss_bad_protocol($match[1]);

          if (!$skip) {
            $attrarr[] = "$attrname='$thisval'";
          }
          $working = 1;
          $mode = 0;
          $attr = preg_replace("/^'[^']*'(\s+|$)/", '', $attr);
          break;
        }

        if (preg_match("%^([^\s\"']+)(\s+|$)%", $attr, $match)) {
          $thisval = filter_xss_bad_protocol($match[1]);

          if (!$skip) {
            $attrarr[] = "$attrname=\"$thisval\"";
          }
          $working = 1;
          $mode = 0;
          $attr = preg_replace("%^[^\s\"']+(\s+|$)%", '', $attr);
        }
        break;
    }

    if ($working == 0) {
      // Not well formed; remove and try again.
      $attr = preg_replace('/
        ^
        (
        "[^"]*("|$)     # - a string that starts with a double quote, up until the next double quote or the end of the string
        |               # or
        \'[^\']*(\'|$)| # - a string that starts with a quote, up until the next quote or the end of the string
        |               # or
        \S              # - a non-whitespace character
        )*              # any number of the above three
        \s*             # any number of whitespaces
        /x', '', $attr);
      $mode = 0;
    }
  }

  // The attribute list ends with a valueless attribute like "selected".
  if ($mode == 1 && !$skip) {
    $attrarr[] = $attrname;
  }
  return $attrarr;
}

/**
 * Processes an HTML attribute value and strips dangerous protocols from URLs.
 *
 * Borrowed from Drupal.
 * http://api.drupal.org/api/drupal/core%21includes%21common.inc/function/filter_xss_bad_protocol/8
 */
function filter_xss_bad_protocol($string) {
  return check_plain(perseus_strip_dangerous_protocols($string));
}

/**
 * Parses an array into a valid, rawurlencoded query string.
 *
 * Borrowed from Drupal:
 * http://api.drupal.org/api/drupal/core%21includes%21common.inc/function/drupal_http_build_query/8
 */
function perseus_http_build_query(array $query, $parent = '') {
  $params = array();

  foreach ($query as $key => $value) {
    $key = ($parent ? $parent . '[' . rawurlencode($key) . ']' : rawurlencode($key));

    // Recurse into children.
    if (is_array($value)) {
      $params[] = perseus_http_build_query($value, $key);
    }
    // If a query parameter value is NULL, only append its key.
    elseif (!isset($value)) {
      $params[] = $key;
    }
    else {
      // For better readability of paths in query strings, we decode slashes.
      $params[] = $key . '=' . str_replace('%2F', '/', rawurlencode($value));
    }
  }

  return implode('&', $params);
}

/**
 * Strip dangerous protocols from a URL.
 *
 * Borrowed from Drupal:
 * http://api.drupal.org/api/drupal/core%21includes%21common.inc/function/drupal_strip_dangerous_protocols/8
 */
function perseus_strip_dangerous_protocols($uri) {
  static $allowed_protocols;

  if (!isset($allowed_protocols)) {
    $allowed_protocols = array_flip(array('ftp', 'http', 'https', 'irc', 'mailto', 'news', 'nntp', 'rtsp', 'sftp', 'ssh', 'tel', 'telnet', 'webcal'));
  }

  // Iteratively remove any invalid protocol found.
  do {
    $before = $uri;
    $colonpos = strpos($uri, ':');
    if ($colonpos > 0) {
      // We found a colon, possibly a protocol. Verify.
      $protocol = substr($uri, 0, $colonpos);
      // If a colon is preceded by a slash, question mark or hash, it cannot
      // possibly be part of the URL scheme. This must be a relative URL, which
      // inherits the (safe) protocol of the base document.
      if (preg_match('![/?#]!', $protocol)) {
        break;
      }
      // Check if this is a disallowed protocol. Per RFC2616, section 3.2.3
      // (URI Comparison) scheme comparison must be case-insensitive.
      if (!isset($allowed_protocols[strtolower($protocol)])) {
        $uri = substr($uri, $colonpos + 1);
      }
    }
  } while ($before != $uri);

  return $uri;
}

/**
 * Encodes a Drupal path for use in a URL.
 *
 * Borrowed from Drupal:
 * http://api.drupal.org/api/drupal/core%21includes%21common.inc/function/drupal_encode_path/8
 */
function perseus_encode_path($path) {
  return str_replace('%2F', '/', rawurlencode($path));
}

/**
 *
 */
function perseus_validate_utf8($text) {
  if (strlen($text) == 0) {
    return TRUE;
  }
  // With the PCRE_UTF8 modifier 'u', preg_match() fails silently on strings
  // containing invalid UTF-8 byte sequences. It does not reject character
  // codes above U+10FFFF (represented by 4 or more octets), though.
  return (preg_match('/^./us', $text) == 1);
}

/**
 * Generate a safe and properly formatted URL.
 */
function url($path = NULL, array $options = array()) {
  global $system;

  // Merge in defaults.
  $options += array(
    'fragment' => '',
    'query' => array(),
    'absolute' => FALSE,
    'alias' => FALSE,
    'prefix' => '',
  );

  if (!isset($options['external'])) {
    // Return an external link if $path contains an allowed absolute URL. Only
    // call the slow drupal_strip_dangerous_protocols() if $path contains a ':'
    // before any / ? or #. Note: we could use url_is_external($path) here, but
    // that would require another function call, and performance inside url() is
    // critical.
    $colonpos = strpos($path, ':');
    $options['external'] = ($colonpos !== FALSE && !preg_match('![/?#]!', substr($path, 0, $colonpos)) && perseus_strip_dangerous_protocols($path) == $path);
  }

  // Preserve the original path before altering or aliasing.
  $original_path = $path;

  if (isset($options['fragment']) && $options['fragment'] !== '') {
    $options['fragment'] = '#' . $options['fragment'];
  }

  if ($options['external']) {
    // Split off the fragment.
    if (strpos($path, '#') !== FALSE) {
      list($path, $old_fragment) = explode('#', $path, 2);
      // If $options contains no fragment, take it over from the path.
      if (isset($old_fragment) && !$options['fragment']) {
        $options['fragment'] = '#' . $old_fragment;
      }
    }
    // Append the query.
    if ($options['query']) {
      $path .= (strpos($path, '?') !== FALSE ? '&' : '?') . perseus_http_build_query($options['query']);
    }
    if (isset($options['https'])) {
      if ($options['https'] === TRUE) {
        $path = str_replace('http://', 'https://', $path);
      }
      elseif ($options['https'] === FALSE) {
        $path = str_replace('https://', 'http://', $path);
      }
    }
    // Reassemble.
    return $path . $options['fragment'];
  }

  $base = $options['absolute'] ? $options['base_url'] . '/' : $system->base_path;
  $prefix = empty($path) ? rtrim($options['prefix'], '/') : $options['prefix'];

  $path = perseus_encode_path($prefix . $path);
  $query = $options['query'] ? ('?' . perseus_http_build_query($options['query'])) : '';

  return $base . $path . $query . $options['fragment'];
}

/**
 * Aliases for debug var dump.
 */
function pd($var) {
  \Perseus\Debug::dump($var);
}
function dd($var) {
  \Perseus\Debug::dump($var);
}
function kd($var) {
  \Perseus\Debug::kdump($var);
}


/**
 * Generate a unique, machine-safe ID.
 *
 * Borrowed from Drupal.
 *
 */
function unique_id($string) {
  static $seen_ids_init;

  if (!isset($seen_ids_init)) {
    if (empty($_POST['ajax_html_ids'])) {
      $seen_ids_init = array();
    }
    else {
      $ajax_html_ids = explode(' ', $_POST['ajax_html_ids']);
      foreach ($ajax_html_ids as $seen_id) {
        // We rely on '--' being used solely for separating a base id from the
        // counter, which this function ensures when returning an id.
        $parts = explode('--', $seen_id, 2);
        if (!empty($parts[1]) && is_numeric($parts[1])) {
          list($seen_id, $i) = $parts;
        }
        else {
          $i = 1;
        }
        if (!isset($seen_ids_init[$seen_id]) || ($i > $seen_ids_init[$seen_id])) {
          $seen_ids_init[$seen_id] = $i;
        }
      }
    }
  }

  static $seen_ids;

  $id = strtr(strtolower($string), array(' ' => '-', '_' => '-', '[' => '-', ']' => ''));

  // As defined in http://www.w3.org/TR/html4/types.html#type-name, HTML IDs can
  // only contain letters, digits ([0-9]), hyphens ("-"), underscores ("_"),
  // colons (":"), and periods ("."). We strip out any character not in that
  // list. Note that the CSS spec doesn't allow colons or periods in identifiers
  // (http://www.w3.org/TR/CSS21/syndata.html#characters), so we strip those two
  // characters as well.
  $id = preg_replace('/[^A-Za-z0-9\-_]/', '', $id);

  // Removing multiple consecutive hyphens.
  $id = preg_replace('/\-+/', '-', $id);
  // Ensure IDs are unique by appending a counter after the first occurrence.
  // The counter needs to be appended with a delimiter that does not exist in
  // the base ID. Requiring a unique delimiter helps ensure that we really do
  // return unique IDs and also helps us re-create the $seen_ids array during
  // Ajax requests.
  if (isset($seen_ids[$id])) {
    $id = $id . '--' . ++$seen_ids[$id];
  }
  else {
    $seen_ids[$id] = 1;
  }

  return $id;
}

function get_us_states() {
  return array(
    'AL'=>"Alabama",
    'AK'=>"Alaska",
    'AZ'=>"Arizona",
    'AR'=>"Arkansas",
    'CA'=>"California",
    'CO'=>"Colorado",
    'CT'=>"Connecticut",
    'DE'=>"Delaware",
    'DC'=>"District Of Columbia",
    'FL'=>"Florida",
    'GA'=>"Georgia",
    'HI'=>"Hawaii",
    'ID'=>"Idaho",
    'IL'=>"Illinois",
    'IN'=>"Indiana",
    'IA'=>"Iowa",
    'KS'=>"Kansas",
    'KY'=>"Kentucky",
    'LA'=>"Louisiana",
    'ME'=>"Maine",
    'MD'=>"Maryland",
    'MA'=>"Massachusetts",
    'MI'=>"Michigan",
    'MN'=>"Minnesota",
    'MS'=>"Mississippi",
    'MO'=>"Missouri",
    'MT'=>"Montana",
    'NE'=>"Nebraska",
    'NV'=>"Nevada",
    'NH'=>"New Hampshire",
    'NJ'=>"New Jersey",
    'NM'=>"New Mexico",
    'NY'=>"New York",
    'NC'=>"North Carolina",
    'ND'=>"North Dakota",
    'OH'=>"Ohio",
    'OK'=>"Oklahoma",
    'OR'=>"Oregon",
    'PA'=>"Pennsylvania",
    'RI'=>"Rhode Island",
    'SC'=>"South Carolina",
    'SD'=>"South Dakota",
    'TN'=>"Tennessee",
    'TX'=>"Texas",
    'UT'=>"Utah",
    'VT'=>"Vermont",
    'VA'=>"Virginia",
    'WA'=>"Washington",
    'WV'=>"West Virginia",
    'WI'=>"Wisconsin",
    'WY'=>"Wyoming",
  );
}

function get_canadian_provinces() {
  return array(
    "AB"=>"Alberta",
    "BC"=>"British Columbia",
    "MB"=>"Manitoba",
    "NL"=>"Newfoundland and Labrador",
    "NB"=>"New Brunswick",
    "NT"=>"Northwest Territories",
    "NS"=>"Nova Scotia",
    "NU"=>"Nunavut",
    "ON"=>"Ontario",
    "PE"=>"Prince Edward Island",
    "QC"=>"Quebec",
    "SK"=>"Saskatchewan",
    "YT"=>"Yukon Territory",
  );
}

function get_countries() {
  return array(
    'AF' => 'Afghanistan',
    'AX' => 'Aland Islands',
    'AL' => 'Albania',
    'DZ' => 'Algeria',
    'AS' => 'American Samoa',
    'AD' => 'Andorra',
    'AO' => 'Angola',
    'AI' => 'Anguilla',
    'AQ' => 'Antarctica',
    'AG' => 'Antigua and Barbuda',
    'AR' => 'Argentina',
    'AM' => 'Armenia',
    'AW' => 'Aruba',
    'AU' => 'Australia',
    'AT' => 'Austria',
    'AZ' => 'Azerbaijan',
    'BS' => 'Bahamas',
    'BH' => 'Bahrain',
    'BD' => 'Bangladesh',
    'BB' => 'Barbados',
    'BY' => 'Belarus',
    'BE' => 'Belgium',
    'BZ' => 'Belize',
    'BJ' => 'Benin',
    'BM' => 'Bermuda',
    'BT' => 'Bhutan',
    'BO' => 'Bolivia, Plurinational State of',
    'BQ' => 'Bonaire, Sint Eustatius and Saba',
    'BA' => 'Bosnia and Herzegovina',
    'BW' => 'Botswana',
    'BV' => 'Bouvet Island',
    'BR' => 'Brazil',
    'IO' => 'British Indian Ocean Territory',
    'BN' => 'Brunei Darussalam',
    'BG' => 'Bulgaria',
    'BF' => 'Burkina Faso',
    'BI' => 'Burundi',
    'KH' => 'Cambodia',
    'CM' => 'Cameroon',
    'CA' => 'Canada',
    'CV' => 'Cape Verde',
    'KY' => 'Cayman Islands',
    'CF' => 'Central African Republic',
    'TD' => 'Chad',
    'CL' => 'Chile',
    'CN' => 'China',
    'CX' => 'Christmas Island',
    'CC' => 'Cocos (Keeling) Islands',
    'CO' => 'Colombia',
    'KM' => 'Comoros',
    'CG' => 'Congo',
    'CD' => 'Congo, the Democratic Republic of the',
    'CK' => 'Cook Islands',
    'CR' => 'Costa Rica',
    'CI' => 'Côte d\'Ivoire',
    'HR' => 'Croatia',
    'CU' => 'Cuba',
    'CW' => 'Curaçao',
    'CY' => 'Cyprus',
    'CZ' => 'Czech Republic',
    'DK' => 'Denmark',
    'DJ' => 'Djibouti',
    'DM' => 'Dominica',
    'DO' => 'Dominican Republic',
    'EC' => 'Ecuador',
    'EG' => 'Egypt',
    'SV' => 'El Salvador',
    'GQ' => 'Equatorial Guinea',
    'ER' => 'Eritrea',
    'EE' => 'Estonia',
    'ET' => 'Ethiopia',
    'FK' => 'Falkland Islands (Malvinas)',
    'FO' => 'Faroe Islands',
    'FJ' => 'Fiji',
    'FI' => 'Finland',
    'FR' => 'France',
    'GF' => 'French Guiana',
    'PF' => 'French Polynesia',
    'TF' => 'French Southern Territories',
    'GA' => 'Gabon',
    'GM' => 'Gambia',
    'GE' => 'Georgia',
    'DE' => 'Germany',
    'GH' => 'Ghana',
    'GI' => 'Gibraltar',
    'GR' => 'Greece',
    'GL' => 'Greenland',
    'GD' => 'Grenada',
    'GP' => 'Guadeloupe',
    'GU' => 'Guam',
    'GT' => 'Guatemala',
    'GG' => 'Guernsey',
    'GN' => 'Guinea',
    'GW' => 'Guinea-Bissau',
    'GY' => 'Guyana',
    'HT' => 'Haiti',
    'HM' => 'Heard Island and McDonald Islands',
    'VA' => 'Holy See (Vatican City State)',
    'HN' => 'Honduras',
    'HK' => 'Hong Kong',
    'HU' => 'Hungary',
    'IS' => 'Iceland',
    'IN' => 'India',
    'ID' => 'Indonesia',
    'IR' => 'Iran, Islamic Republic of',
    'IQ' => 'Iraq',
    'IE' => 'Ireland',
    'IM' => 'Isle of Man',
    'IL' => 'Israel',
    'IT' => 'Italy',
    'JM' => 'Jamaica',
    'JP' => 'Japan',
    'JE' => 'Jersey',
    'JO' => 'Jordan',
    'KZ' => 'Kazakhstan',
    'KE' => 'Kenya',
    'KI' => 'Kiribati',
    'KP' => 'Korea, Democratic People\'s Republic of',
    'KR' => 'Korea, Republic of',
    'KW' => 'Kuwait',
    'KG' => 'Kyrgyzstan',
    'LA' => 'Lao People\'s Democratic Republic',
    'LV' => 'Latvia',
    'LB' => 'Lebanon',
    'LS' => 'Lesotho',
    'LR' => 'Liberia',
    'LY' => 'Libya',
    'LI' => 'Liechtenstein',
    'LT' => 'Lithuania',
    'LU' => 'Luxembourg',
    'MO' => 'Macao',
    'MK' => 'Macedonia, The Former Yugoslav Republic of',
    'MG' => 'Madagascar',
    'MW' => 'Malawi',
    'MY' => 'Malaysia',
    'MV' => 'Maldives',
    'ML' => 'Mali',
    'MT' => 'Malta',
    'MH' => 'Marshall Islands',
    'MQ' => 'Martinique',
    'MR' => 'Mauritania',
    'MU' => 'Mauritius',
    'YT' => 'Mayotte',
    'MX' => 'Mexico',
    'FM' => 'Micronesia, Federated States of',
    'MD' => 'Moldova, Republic of',
    'MC' => 'Monaco',
    'MN' => 'Mongolia',
    'ME' => 'Montenegro',
    'MS' => 'Montserrat',
    'MA' => 'Morocco',
    'MZ' => 'Mozambique',
    'MM' => 'Myanmar',
    'NA' => 'Namibia',
    'NR' => 'Nauru',
    'NP' => 'Nepal',
    'NL' => 'Netherlands',
    'NC' => 'New Caledonia',
    'NZ' => 'New Zealand',
    'NI' => 'Nicaragua',
    'NE' => 'Niger',
    'NG' => 'Nigeria',
    'NU' => 'Niue',
    'NF' => 'Norfolk Island',
    'MP' => 'Northern Mariana Islands',
    'NO' => 'Norway',
    'OM' => 'Oman',
    'PK' => 'Pakistan',
    'PW' => 'Palau',
    'PS' => 'Palestine, State of',
    'PA' => 'Panama',
    'PG' => 'Papua New Guinea',
    'PY' => 'Paraguay',
    'PE' => 'Peru',
    'PH' => 'Philippines',
    'PN' => 'Pitcairn',
    'PL' => 'Poland',
    'PT' => 'Portugal',
    'PR' => 'Puerto Rico',
    'QA' => 'Qatar',
    'RE' => 'Réunion',
    'RO' => 'Romania',
    'RU' => 'Russian Federation',
    'RW' => 'Rwanda',
    'BL' => 'Saint Barthelemy',
    'SH' => 'Saint Helena, Ascension and Tristan da Cunha',
    'KN' => 'Saint Kitts and Nevis',
    'LC' => 'Saint Lucia',
    'MF' => 'Saint Martin (French part)',
    'PM' => 'Saint Pierre and Miquelon',
    'VC' => 'Saint Vincent and the Grenadines',
    'WS' => 'Samoa',
    'SM' => 'San Marino',
    'ST' => 'Sao Tome and Principe',
    'SA' => 'Saudi Arabia',
    'SN' => 'Senegal',
    'RS' => 'Serbia',
    'SC' => 'Seychelles',
    'SL' => 'Sierra Leone',
    'SG' => 'Singapore',
    'SX' => 'Sint Maarten (Dutch part)',
    'SK' => 'Slovakia',
    'SI' => 'Slovenia',
    'SB' => 'Solomon Islands',
    'SO' => 'Somalia',
    'ZA' => 'South Africa',
    'GS' => 'South Georgia and the South Sandwich Islands',
    'SS' => 'South Sudan',
    'ES' => 'Spain',
    'LK' => 'Sri Lanka',
    'SD' => 'Sudan',
    'SR' => 'Suriname',
    'SJ' => 'Svalbard and Jan Mayen',
    'SZ' => 'Swaziland',
    'SE' => 'Sweden',
    'CH' => 'Switzerland',
    'SY' => 'Syrian Arab Republic',
    'TW' => 'Taiwan, Province of China',
    'TJ' => 'Tajikistan',
    'TZ' => 'Tanzania, United Republic of',
    'TH' => 'Thailand',
    'TL' => 'Timor-Leste',
    'TG' => 'Togo',
    'TK' => 'Tokelau',
    'TO' => 'Tonga',
    'TT' => 'Trinidad and Tobago',
    'TN' => 'Tunisia',
    'TR' => 'Turkey',
    'TM' => 'Turkmenistan',
    'TC' => 'Turks and Caicos Islands',
    'TV' => 'Tuvalu',
    'UG' => 'Uganda',
    'UA' => 'Ukraine',
    'AE' => 'United Arab Emirates',
    'GB' => 'United Kingdom',
    'US' => 'United States',
    'UM' => 'United States Minor Outlying Islands',
    'UY' => 'Uruguay',
    'UZ' => 'Uzbekistan',
    'VU' => 'Vanuatu',
    'VE' => 'Venezuela, Bolivarian Republic of',
    'VN' => 'Viet Nam',
    'VG' => 'Virgin Islands, British',
    'VI' => 'Virgin Islands, U.S.',
    'WF' => 'Wallis and Futuna',
    'EH' => 'Western Sahara',
    'YE' => 'Yemen',
    'ZM' => 'Zambia',
    'ZW' => 'Zimbabwe',
  );
}