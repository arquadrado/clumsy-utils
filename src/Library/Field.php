<?php
namespace Clumsy\Utils\Library;

use Clumsy\Assets\Facade as Asset;
use Collective\Html\FormFacade as Form;
use Illuminate\Support\Facades\Session;

class Field
{
    protected $name;
    protected $label;
    protected $type;
    protected $attributes;
    protected $feedback;

    protected $beforeGroup = null;
    protected $afterGroup = null;

    protected $defaultGroupClass = 'form-group :type';
    protected $defaultClass = ':form-control';

    protected $showLabel = true;

    public function __construct(
        $name = null,
        $label = '',
        array $attributes = []
    ) {

        $this->name = $name;
        $this->label = $label ? $label : $this->labelFromName();
        $this->attributes = $attributes;

        $this->feedback = true;
        $this->type = 'text';

        foreach (['label', 'field', 'input_group'] as $group) {
            if (!isset($this->attributes[$group])) {
                $this->attributes[$group] = [];
            }
        }

        $groupClass = $this->getAttribute('class');
        if (!is_null($groupClass)) {
            $this->setGroupClass($groupClass);
        }

        $class = $this->getAttribute('field.class');
        if (!is_null($class)) {
            $this->setClass($class);
        }
    }

    /**
     * Transform key from array to dot syntax.
     *
     * @param  string $key
     *
     * @return mixed
     */
    protected function transformKey($key)
    {
        return str_replace(['.', '[]', '[', ']'], ['_', '', '.', ''], $key);
    }

    protected function nameForValidation()
    {
        return $this->transformKey($this->name);
    }

    protected function labelFromName()
    {
        return title_case(str_replace('_', ' ', $this->name));
    }

    protected function getAttribute($key, $default = null)
    {
        return array_get($this->attributes, $key, $default);
    }

    protected function setAttribute($key, $value)
    {
        array_set($this->attributes, $key, $value);
    }

    protected function setBooleanAttribute($key, $value)
    {
        if ($value) {
            array_set($this->attributes, $key, 'true');
        } else {
            array_forget($this->attributes, $key);
        }
    }

    protected function updateGroupAttributes($key, $value, $overwrite = false)
    {
        $value = $overwrite ? $value : array_merge(array_get($this->attributes, $key, []), $value);
        return array_set($this->attributes, $key, $value);
    }

    protected function classAttribute($class = null)
    {
        if (!is_array($class)) {
            $class = array_filter(explode(' ', $class));
        }

        return $class;
    }

    protected function getDefaultGroupClass()
    {
        $class = str_replace(':type', $this->type, $this->defaultGroupClass);

        return $this->classAttribute($class);
    }

    protected function getDefaultClass()
    {
        $replace = in_array($this->type, ['checkbox', 'radio']) ? '' : 'form-control';
        $class = str_replace(':form-control', $replace, $this->defaultClass);

        return $this->classAttribute($class);
    }

    public function name($name)
    {
        $this->name = $name;

        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function attribute($key, $value)
    {
        $this->setAttribute($key, $value);

        return $this;
    }

    public function enqueue()
    {
        foreach (func_get_args() as $script) {
            Asset::enqueue($script);
        }

        return $this;
    }

    public function type($type)
    {
        $this->type = $type;

        return $this;
    }

    public function input($name = null, $attributes = [], $overwrite = false)
    {
        $this->name = $name;
        $this->updateGroupAttributes('field', $attributes, $overwrite);

        return $this;
    }

    public function label($label = null, $attributes = [], $overwrite = false)
    {
        $this->label = $label;
        $this->updateGroupAttributes('label', $attributes, $overwrite);

        return $this;
    }

    public function noLabel()
    {
        $this->setAttribute('label.class', 'sr-only');
        $this->showLabel = false;

        return $this;
    }

    public function placeholder($placeholder = null)
    {
        if ($placeholder === null) {
            $placeholder = $this->label;
        }

        $this->setAttribute('field.placeholder', $placeholder);

        return $this;
    }

    public function onlyPlaceholder()
    {
        $this->noLabel()->placeholder();

        return $this;
    }

    public function noFeedback()
    {
        $this->feedback = false;

        return $this;
    }

    public function silent()
    {
        $this->feedback = 'silent';

        return $this;
    }

    public function help($text)
    {
        $this->after("<small class=\"help-block\">{$text}</small>");

        return $this;
    }

    public function data($key, $value)
    {
        $this->setAttribute("field.data-{$key}", $value);

        return $this;
    }

    public function beforeGroup($content = null)
    {
        $this->beforeGroup = $content;

        return $this;
    }

    public function beforeLabel($content = null)
    {
        $this->setAttribute('before_label', $content);

        return $this;
    }

    public function before($content = null)
    {
        $this->setAttribute('before', $content);

        return $this;
    }

    public function afterGroup($content = null)
    {
        $this->afterGroup = $content;

        return $this;
    }

    public function after($content = null)
    {
        $this->setAttribute('after', $content);

        return $this;
    }

    public function prepend($content = null)
    {
        $this->setAttribute('input_group.before', $content);

        return $this;
    }

    public function append($content = null)
    {
        $this->setAttribute('input_group.after', $content);

        return $this;
    }

    public function value($value = null)
    {
        $this->setAttribute('value', $value);

        return $this;
    }

    public function selected($selected = null)
    {
        $this->value($selected);

        return $this;
    }

    public function id($id = null)
    {
        $this->setAttribute('id', $id);

        return $this;
    }

    public function idPrefix($id_prefix = null)
    {
        $this->setAttribute('id_prefix', $id_prefix);

        return $this;
    }

    public function setGroupClass($class = null)
    {
        $this->defaultGroupClass = null;
        $this->setAttribute('class', $this->classAttribute($class));

        return $this;
    }

    public function addGroupClass($class = null)
    {
        $current = (array)$this->getAttribute('class');
        $current[] = $class;
        $this->setAttribute('class', $current);

        return $this;
    }

    public function setClass($class = null)
    {
        $this->defaultClass = null;
        $this->setAttribute('field.class', $this->classAttribute($class));

        return $this;
    }

    public function addClass($class = null)
    {
        $current = (array)$this->getAttribute('field.class');
        $current[] = $class;
        $this->setAttribute('field.class', $current);

        return $this;
    }

    public function options(array $options = [])
    {
        $this->setAttribute('options', $options);

        return $this;
    }

    public function tabindex($tabindex = 1)
    {
        $this->setAttribute('field.tabindex', $tabindex);

        return $this;
    }

    public function cols($cols = 50)
    {
        $this->setAttribute('field.cols', $cols);

        return $this;
    }

    public function rows($rows = 10)
    {
        $this->setAttribute('field.rows', $rows);

        return $this;
    }

    public function checked($checked = true)
    {
        $this->setBooleanAttribute('checked', $checked);

        return $this;
    }

    public function disabled($disabled = true)
    {
        $this->setBooleanAttribute('field.disabled', $disabled);

        return $this;
    }

    public function required($required = true)
    {
        $this->setBooleanAttribute('field.required', $required);

        return $this;
    }

    public function readonly($readonly = true)
    {
        $this->setBooleanAttribute('field.readonly', $readonly);

        return $this;
    }

    public function multiple($multiple = true)
    {
        $this->setBooleanAttribute('field.multiple', $multiple);

        return $this;
    }

    public function toHTML()
    {
        $name = $this->name;
        $label = $this->label;
        $type = $this->type;
        $attributes = $this->attributes;
        $feedback = $this->feedback;

        $input_group = array_pull($attributes, 'input_group');

        $label_attributes = array_pull($attributes, 'label');

        $field_attributes = array_pull($attributes, 'field');
        $field_attributes['class'] = implode(' ', array_merge(
            $this->getDefaultClass(),
            (array)$this->getAttribute('field.class')
        ));

        $defaults = [
            'value'        => null,
            'before_label' => null,
            'before'       => null,
            'after'        => null,
            'id'           => null,
            'id_prefix'    => null,
            'checked'      => null,
        ];

        $attributes = array_merge($defaults, $attributes);
        extract($attributes, EXTR_SKIP);

        if (!$id) {
            $id = $id_prefix.$name;
        }

        if (!isset($field_attributes['id'])) {
            $field_attributes['id'] = $id;
        }

        $groupClass = array_merge($this->getDefaultGroupClass(), (array)$this->getAttribute('class'));

        if ($feedback && $name && Session::has('errors')) {
            $errors = Session::get('errors');

            if ($errors->has($this->nameForValidation())) {
                $groupClass[] = 'has-error';
                $groupClass[] = 'has-feedback';

                $after .= '<span class="glyphicon glyphicon-remove form-control-feedback"></span>';
                if ($feedback !== 'silent') {
                    $after .= '<p class="help-block">'.$errors->first($this->nameForValidation()).'</p>';
                }
            }
        }

        $groupClass = implode(' ', $groupClass);

        $output = $this->beforeGroup;

        $output .= "<div class=\"$groupClass\">";

        $output .= $before_label;

        $output .= Form::label((isset($label_attributes['for']) ? $label_attributes['for'] : $id), $label, $label_attributes);

        $output .= $before;

        if (sizeof($input_group)) {
            $output .= '<div class="input-group">';
            if (isset($input_group['before'])) {
                $group_type = strpos($input_group['before'], 'button') ? 'btn' : 'addon';
                $output .= '<div class="input-group-'.$group_type.'">'.$input_group['before'].'</div>';
            }
        }

        if (in_array($type, ['password', 'file'])) {
            $output .= Form::$type($name, $field_attributes);
        } elseif (in_array($type, ['select'])) {
            $output .= Form::$type($name, $options, $value, $field_attributes);
        } elseif (in_array($type, ['checkbox', 'radio'])) {
            if (is_null($value) || $value === false) {
                $value = 1;
            }
            $field = Form::$type($name, $value, $checked, $field_attributes);
            if ($this->showLabel) {
                $label_end = strpos($output, '>', strpos($output, '<label'))+1;
                $output = substr_replace($output, $field, $label_end, 0);
            } else {
                $output .= $field;
            }
        } else {
            $output .= Form::$type($name, $value, $field_attributes);
        }

        if (sizeof($input_group)) {
            if (isset($input_group['after'])) {
                $group_type = strpos($input_group['after'], 'button') ? 'btn' : 'addon';
                $output .= '<div class="input-group-'.$group_type.'">'.$input_group['after'].'</div>';
            }

            $output .= '</div>';
        }

        $output .= $after;

        $output .= '</div>';

        $output .= $this->afterGroup;

        return $output;
    }

    public function __toString()
    {
        return $this->toHTML();
    }
}