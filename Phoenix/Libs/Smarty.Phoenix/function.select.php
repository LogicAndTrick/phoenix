<?php

function smarty_function_select($params, $template)
{
    $defaults = array(
        'items' => null,
        'textfield' => 'Name',
        'valuefield' => 'ID',
        'type' => 'select',
        'multiple' => false,
        'multiple_style' => 'select',
        'multiple_size' => 5,
        'multiple_separator' => '<br />',
        'all_selected' => false,
        'label_separator' => ' ',
        'disabled' => false,
        'model' => null,
        'first_text' => null,
        'first_value' => null,
        'ignore_post' => false
    );
    $params = array_merge($defaults, $params);

    $selected_vals = array();

    $multi = $params['multiple'] === true;
    if (!$multi) {
        $selected_value = null;
        $model = $params['model'];
        if (Post::IsPostBack() && Post::Get($params['for']) != null && !$params['ignore_post']) {
            $selected_value = Post::Get($params['for']);
        } else if (isset($params['selected_value']) && $params['selected_value'] != null) {
            $selected_value = $params['selected_value'];
        } else if ($model != null && ($model instanceof Model || $model instanceof CustomQueryRow)) {
            $selected_value = $model->{$params['for']};
        }

        if ($selected_value !== null) {
            $selected_vals[] = $selected_value;
        }
    } else {
        $sv = null;
        if (Post::IsPostBack() && Post::Get($params['for']) != null && !$params['ignore_post']) {
            $sv = Post::Get($params['for']);
        } else if (isset($params['selected_value']) && is_array($params['selected_value'])) {
            $sv = $params['selected_value'];
        } else if (isset($params['selected_values']) && is_array($params['selected_values'])) {
            $sv = $params['selected_values'];
        }
        if ($sv !== null && is_array($sv)) {
            foreach ($sv as $k => $ex) {
                if (is_object($ex) && property_exists($ex, $params['valuefield'])) {
                    $selected_vals[] = $ex->{$params['valuefield']};
                } else if (is_numeric($ex) || is_string($ex)) {
                    $selected_vals[] = $ex;
                }
            }
        }
    }

    $htmlattr = array();

    if ($params['disabled'] === true) {
        $htmlattr['disabled'] = 'disabled';
    }

    $htmlattr['name'] = $params['for'] . ($multi ? '[]' : '');
    $htmlattr['id'] = 'form_'.$params['for'];

    if ($multi) {
        $htmlattr['multiple'] = 'muiltiple';
        $htmlattr['size'] = $params['multiple_size'];
    }

    foreach ($params as $key => $value) {
        if (substr($key, 0, 5) == 'html_') {
            $htmlattr[str_ireplace('_', '-', substr($key, 5))] = $value;
        }
    }

    if ($params['items'] === null) $params['items'] = $template->getVariable($params['for'])->value;
    if ($params['items'] === null || !is_array($params['items'])) $params['items'] = array();

    if ($multi && $params['multiple_style'] == 'checkbox') {
        $select = '';
        foreach ($params['items'] as $item) {
            $selected = $params['all_selected'] || array_search($item->{$params['valuefield']}, $selected_vals) !== false;
            $val = htmlspecialchars($item->{$params['valuefield']});
            $select .=  '<label class="checkbox" for="' . $htmlattr['id'] . '_' . $val . '">' .
                        '<input type="checkbox" ' .
                        'name="' . htmlspecialchars($htmlattr['name']) . '" ' .
                        'value="' . $val . '" ' .
                        'id="' . $htmlattr['id'] . '_' . $val . '"' .
                        ($selected ? ' checked="checked"' : '') .
                        '/>' . $params['label_separator'] .
                         htmlspecialchars($item->{$params['textfield']}) .
                       '</label>' .
                       $params['multiple_separator'] . "\n";
        }
    } else if ($params['type'] == 'radio') {
        $select = '';
        foreach ($params['items'] as $item) {
            $selected = array_search($item->{$params['valuefield']}, $selected_vals) !== false;
            $val = htmlspecialchars($item->{$params['valuefield']});
            $select .= '<input type="radio" ' .
                        'name="' . htmlspecialchars($htmlattr['name']) . '" ' .
                        'value="' . $val . '" ' .
                        'id="' . $htmlattr['id'] . '_' . $val . '"' .
                        ($selected ? ' checked="checked"' : '') .
                        '/>' . $params['label_separator'] .
                       '<label for="' . $htmlattr['id'] . '_' . $val . '">' .
                         htmlspecialchars($item->{$params['textfield']}) .
                       '</label>' .
                       $params['multiple_separator'] . "\n";
        }
    } else {
        $select = '<select';

        foreach ($htmlattr as $key => $value) {
            $select .= ' '.$key.'="'.htmlspecialchars($value).'"';
        }

        $select .= '>';

        if (!$multi && $params['first_value'] !== null && $params['first_text'] !== null) {
            $selected = count($selected_vals) == 0 || (count($selected_vals) == 1
                            && ($selected_vals[0] === null || $selected_vals[0] == $params['first_value']));
            $select .= "\n" . '<option value="' . $params['first_value'] . '"'
                       . ($selected ? ' selected="selected"' : '')
                       . '>' . $params['first_text'] . '</option>';
        }

        foreach ($params['items'] as $item) {
            $selected = $params['all_selected'] || array_search($item->{$params['valuefield']}, $selected_vals) !== false;
            $select .= "\n"
                    .'<option value="' . htmlspecialchars($item->{$params['valuefield']}) . '"' .
                       ($selected ? ' selected="selected"' : '') .
                     '>'
                    .htmlspecialchars($item->{$params['textfield']})
                    .'</option>';
        }
    
        $select .= '</select>';
    }

    return $select;
}

