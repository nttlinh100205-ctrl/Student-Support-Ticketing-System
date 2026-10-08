
@props(['name' => 'rating', 'value' => 0, 'label' => 'Mức độ hài lòng'])
<fieldset class="uni-stars">
<legend>{{ $label }}</legend>
@foreach(range(1,5) as $score)<label>
<input type="radio" name="{{ $name }}" value="{{ $score }}" @checked((int)$value === $score) required>
<span>{{ $score }} ★</span>
</label>
@endforeach</fieldset>
