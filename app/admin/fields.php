<?php
declare(strict_types=1);

/** Affiche un champ de formulaire de l'admin. */
function admin_field(string $name, array $def, mixed $value): string
{
    $id = 'f_' . $name;
    $type = $def['type'];
    $label = e($def['label']) . (!empty($def['required']) ? ' <span class="req">*</span>' : '');
    $req = !empty($def['required']) ? ' required' : '';
    $help = !empty($def['help']) ? '<small class="help">' . e($def['help']) . '</small>' : '';
    if (!empty($def['format_help'])) {
        $help .= '<small class="help">Mise en forme : laissez une ligne vide entre les paragraphes · <code>**gras**</code> · <code>## Sous-titre</code> · lignes commençant par <code>- </code> pour une liste.</small>';
    }
    $cls = 'field' . (!empty($def['half']) ? ' half' : '');

    switch ($type) {
        case 'heading':
            return '<div class="field form-heading"><h3>' . e($def['label']) . '</h3>' . $help . '</div>';
        case 'file':
            $input = '';
            if ($value) {
                $input .= '<div class="image-current"><a class="file-chip" href="' . e(media((string) $value)) . '" target="_blank" rel="noopener">📄 Voir le PDF actuel</a>'
                    . '<label class="remove"><input type="checkbox" name="' . $name . '_remove" value="1"> Supprimer ce fichier</label></div>';
            }
            $input .= '<input id="' . $id . '" type="file" name="' . $name . '" accept="application/pdf">';
            $help .= '<small class="help">PDF uniquement — 10 Mo maximum.</small>';
            break;
        case 'textarea':
            $input = '<textarea id="' . $id . '" name="' . $name . '" rows="' . (int) ($def['rows'] ?? 4) . '"' . $req . '>' . e($value) . '</textarea>';
            break;
        case 'select':
            $input = '<select id="' . $id . '" name="' . $name . '">';
            if (isset($def['empty'])) {
                $input .= '<option value="">' . e($def['empty']) . '</option>';
            }
            foreach (field_options($def) as $k => $lbl) {
                $input .= '<option value="' . e($k) . '"' . ((string) $value === (string) $k ? ' selected' : '') . '>' . e($lbl) . '</option>';
            }
            $input .= '</select>';
            break;
        case 'checkbox':
            $checked = $value === null ? !empty($def['default']) : (bool) $value;
            return '<div class="' . $cls . ' field-check"><label class="switch-label"><input type="checkbox" role="switch" name="' . $name . '" value="1"' . ($checked ? ' checked' : '') . '>'
                . '<span class="switch" aria-hidden="true"></span><span>' . e($def['label']) . '</span></label>' . $help . '</div>';
        case 'image':
            $input = '';
            if ($value) {
                $input .= '<div class="image-current"><img src="' . e(media((string) $value)) . '" alt="">'
                    . '<label class="remove"><input type="checkbox" name="' . $name . '_remove" value="1"> Supprimer cette image</label></div>';
            }
            $input .= '<input id="' . $id . '" type="file" name="' . $name . '" accept="image/jpeg,image/png,image/webp,image/gif" data-preview>';
            $help .= '<small class="help">JPG, PNG, WEBP ou GIF — ' . (int) config('upload_max_mb', 8) . ' Mo maximum.</small>';
            break;
        case 'money':
        case 'number':
            $v = $value === null || $value === '' ? ($def['default'] ?? '') : $value;
            if ($type === 'money' && is_numeric($v)) {
                $v = rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
            }
            $suffix = $type === 'money' ? '<span class="suffix">' . e(setting('currency', 'Ar')) . '</span>' : '';
            $input = '<div class="input-suffix"><input id="' . $id . '" type="text" inputmode="decimal" name="' . $name . '" value="' . e($v) . '"' . $req . '>' . $suffix . '</div>';
            break;
        default:
            $htmlType = ['email' => 'email', 'url' => 'url', 'date' => 'date'][$type] ?? 'text';
            $input = '<input id="' . $id . '" type="' . $htmlType . '" name="' . $name . '" value="' . e($value) . '"' . $req
                . ($type === 'slug' ? ' pattern="[a-z0-9-]*" placeholder="genere-automatiquement"' : '') . '>';
    }
    return '<div class="' . $cls . '"><label for="' . $id . '">' . $label . '</label>' . $input . $help . '</div>';
}

/** Valeur lisible d'une cellule dans la liste. */
function admin_cell(string $col, array $ent, array $row, array $options): string
{
    $def = $ent['fields'][$col] ?? ['type' => 'text'];
    $v = $row[$col] ?? null;
    if ($col === 'icon') {
        return '<span class="cell-icon">' . icon((string) $v, 22) . '</span>';
    }
    return match ($def['type']) {
        'image' => $v ? '<img class="thumb" src="' . e(media($v)) . '" alt="">' : '<span class="thumb thumb-empty">' . icon($ent['icon'], 20) . '</span>',
        'checkbox' => $v ? '<span class="pill pill-ok">Oui</span>' : '<span class="pill">Non</span>',
        'url' => $v ? '<span class="pill pill-ok">Oui</span>' : '<span class="pill">—</span>',
        'file' => $v ? '<a href="' . e(media($v)) . '" target="_blank" rel="noopener">PDF</a>' : '—',
        'money' => $v !== null ? e(money($v)) : '—',
        'select' => $col === 'status'
            ? '<span class="pill pill-' . e($v) . '">' . e($options[$col][$v] ?? $v) . '</span>'
            : e($options[$col][(string) $v] ?? '—'),
        default => e(excerpt((string) $v, 80)),
    };
}
