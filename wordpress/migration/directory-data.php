<?php
/** Pure transformations for a reviewed public Directory export. No database writes. */
namespace Finelevate\Migration;

function require_staging_target($url) {
    if ($url !== 'https://stg.finelevate360.com' && $url !== 'https://stg.finelevate360.com/') {
        throw new \InvalidArgumentException('Only the exact staging HTTPS origin is accepted.');
    }
}

function positive_id($id) {
    if ((!is_int($id) && !(is_string($id) && preg_match('/^[1-9][0-9]*$/D', $id))) || (int)$id <= 0 || (string)(int)$id !== (string)$id) {
        throw new \InvalidArgumentException('Expected a canonical positive post ID.');
    }
    return (int)$id;
}

function validate_id_map($map) {
    if (!is_array($map)) throw new \InvalidArgumentException('Expected an explicit ID map.');
    $destinations = [];
    foreach ($map as $source => $destination) {
        positive_id($source);
        $destination = positive_id($destination);
        if (isset($destinations[$destination])) throw new \InvalidArgumentException('ID map must be one-to-one.');
        $destinations[$destination] = true;
    }
}

function mapped_id($source, $map) {
    $source = positive_id($source);
    if (!array_key_exists($source, $map)) throw new \InvalidArgumentException('Missing mapping for source ID ' . $source);
    return positive_id($map[$source]);
}

/** Only scalar/array data is accepted; PHP objects and excessively deep/cyclic data are refused. */
function assert_plain_data($value, $depth = 0) {
    if ($depth > 64 || is_object($value) || is_resource($value)) throw new \InvalidArgumentException('Unsupported exported value.');
    if (is_array($value)) foreach ($value as $item) assert_plain_data($item, $depth + 1);
}

function decode_array($value) {
    if (is_array($value)) { assert_plain_data($value); return $value; }
    if (!is_string($value) || strlen($value) > 1048576 || !preg_match('/^a:[0-9]+:\{/', $value)) {
        throw new \InvalidArgumentException('Expected a native or serialized PHP array.');
    }
    set_error_handler(function () { throw new \InvalidArgumentException('Malformed serialized array.'); });
    try {
        $decoded = unserialize($value, ['allowed_classes' => false, 'max_depth' => 64]);
    } finally { restore_error_handler(); }
    assert_plain_data($decoded);
    // Canonical re-serialization also rejects trailing bytes and malformed string lengths.
    if (!is_array($decoded) || serialize($decoded) !== $value) throw new \InvalidArgumentException('Noncanonical serialized array.');
    return $decoded;
}

function remap_attribute_rows($rows, $attribute_map) {
    validate_id_map($attribute_map);
    $result = [];
    foreach (decode_array($rows) as $source => $value) {
        $destination = mapped_id($source, $attribute_map);
        // Preserve legacy scalar values and all four channel dimensions verbatim.
        $result[$destination] = $value;
    }
    return $result;
}

/** Remap both parallel rating representations without changing rating semantics. */
function remap_rating_meta($meta, $attribute_map) {
    validate_id_map($attribute_map);
    $result = [];
    foreach ($meta as $key => $value) {
        if ($key === '_finelevate360_item_attrs') {
            $native = remap_attribute_rows($value, $attribute_map);
            $result[$key] = is_string($value) ? serialize($native) : $native;
        } elseif (preg_match('/^_finelevate360_attr_([1-9][0-9]*)$/D', $key, $match)) {
            assert_plain_data($value);
            $result['_finelevate360_attr_' . mapped_id($match[1], $attribute_map)] = $value;
        } else {
            // Do not turn an incomplete metadata inventory into a broad allowlist.
            throw new \InvalidArgumentException('Unreviewed rating metadata key: ' . $key);
        }
    }
    return $result;
}

/** Native research arrays, JSON, and the plugin's checked gzip envelope. */
function remap_research($value, $attribute_map) {
    validate_id_map($attribute_map);
    $was_json = is_string($value);
    if ($was_json) {
        if (strlen($value) > 1048576) throw new \InvalidArgumentException('Research JSON exceeds limit.');
        $value = json_decode($value, true, 64, JSON_THROW_ON_ERROR);
    }
    assert_plain_data($value);
    if (!is_array($value)) throw new \InvalidArgumentException('Expected research data.');
    $envelope = isset($value['encoding']);
    if ($envelope) {
        if ($value['encoding'] !== 'gzip-base64-json-v1' || !is_string($value['data'] ?? null) || strlen($value['data']) > 262144 || !is_int($value['uncompressed_bytes'] ?? null) || $value['uncompressed_bytes'] < 1 || $value['uncompressed_bytes'] > 1048576 || !is_string($value['sha256'] ?? null)) {
            throw new \InvalidArgumentException('Invalid research transport envelope.');
        }
        $compressed = base64_decode($value['data'], true);
        $json = $compressed === false ? false : @gzdecode($compressed, 1048576);
        if (!is_string($json) || strlen($json) !== $value['uncompressed_bytes'] || !hash_equals($value['sha256'], hash('sha256', $json))) {
            throw new \InvalidArgumentException('Research envelope checksum mismatch.');
        }
        $value = json_decode($json, true, 64, JSON_THROW_ON_ERROR);
    }
    if (!is_array($value) || !isset($value['cells']) || !is_array($value['cells'])) throw new \InvalidArgumentException('Missing research cells.');
    $cells = [];
    foreach ($value['cells'] as $source => $cell) $cells[mapped_id($source, $attribute_map)] = $cell;
    $value['cells'] = $cells;
    if ($envelope) {
        $json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        if (strlen($json) > 1048576) throw new \InvalidArgumentException('Mapped research exceeds limit.');
        $data = base64_encode(gzencode($json));
        if (strlen($data) > 262144) throw new \InvalidArgumentException('Mapped envelope exceeds limit.');
        $value = ['encoding' => 'gzip-base64-json-v1', 'data' => $data, 'uncompressed_bytes' => strlen($json), 'sha256' => hash('sha256', $json)];
    }
    return $was_json ? json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) : $value;
}

/** Translate code-level research rules using the same complete post ID map. */
function remap_research_rules($post_map) {
    validate_id_map($post_map);
    $map_ids = function ($ids) use ($post_map) { return array_map(function ($id) use ($post_map) { return mapped_id($id, $post_map); }, $ids); };
    $aliases = [];
    foreach ([762=>54,763=>603,1098=>1187,1122=>1188,1925=>1926,2034=>2035] as $old => $canonical) $aliases[mapped_id($old, $post_map)] = mapped_id($canonical, $post_map);
    return [
        'aliases' => $aliases,
        'items' => [
            'hr' => $map_ids([1007,1010,1064,1067,1070,1072,1075,1079,1082,1087,1090,1093,1096,1101,1104,1107,1110,1113,1116,2164]),
            'sl' => $map_ids([1952,1954,1956,1958,1960,1962,1964,1965,1967,1968,1970,1972]),
        ],
        'pages' => ['hr' => mapped_id(1042, $post_map), 'sl' => mapped_id(1974, $post_map)],
        // Keep the archived definitions in the map; publication status controls aliases.
        'attributes' => [
            'hr' => $map_ids([1122,1188,1190,1192,1194,1197,1200,1204,1206,1208,1210,1212,1214,1216,1219,1221,1224,1227,1229,1231,1233,1237,1239,1241,1243,1246,1248]),
            'sl' => $map_ids(range(1925,1951)),
        ],
    ];
}
