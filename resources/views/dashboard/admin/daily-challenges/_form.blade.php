@php
    $existingOptions = $dailyChallenge?->options ?? collect();
    $correctIndex = old('correct_answer');
    if ($correctIndex === null && $existingOptions->count()) {
        $found = $existingOptions->search(fn ($o) => $o->is_correct);
        $correctIndex = $found === false ? 0 : $found;
    }
    $correctIndex = $correctIndex === null ? 0 : (int) $correctIndex;
    $challengeDate = old('challenge_date', optional($dailyChallenge?->challenge_date)->format('Y-m-d') ?? now()->format('Y-m-d'));
    $statusChecked = old('status', $dailyChallenge?->status ?? 1);
@endphp

<div class="row">
    <!-- Grade -->
    <div class="col-md-4 col-12">
        <div class="form-group">
            <label for="grade_id" class="col-form-label-sm">{{ __('general.Grade') }}</label>
            <select name="grade_id" id="grade_id" class="form-control form-control-sm @error('grade_id') is-invalid @enderror" required>
                <option value="">{{ __('general.Select Grade') }}</option>
                @foreach($grades as $grade)
                    <option value="{{ $grade->id }}" {{ old('grade_id', $dailyChallenge?->grade_id) == $grade->id ? 'selected' : '' }}>
                        {{ $grade->name_ar }}
                    </option>
                @endforeach
            </select>
            @error('grade_id')<span class="col-form-label-sm text-danger">{{ $message }}</span>@enderror
        </div>
    </div>

    <!-- Semester -->
    <div class="col-md-4 col-12">
        <div class="form-group">
            <label for="semester_id" class="col-form-label-sm">{{ __('general.Semester') }}</label>
            <select name="semester_id" id="semester_id" class="form-control form-control-sm @error('semester_id') is-invalid @enderror" required>
                <option value="">{{ __('general.Select Semester') }}</option>
                @foreach($semesters as $semester)
                    <option value="{{ $semester->id }}" {{ old('semester_id', $dailyChallenge?->semester_id) == $semester->id ? 'selected' : '' }}>
                        {{ $semester->name_ar }}
                    </option>
                @endforeach
            </select>
            @error('semester_id')<span class="col-form-label-sm text-danger">{{ $message }}</span>@enderror
        </div>
    </div>

    <!-- Subject -->
    <div class="col-md-4 col-12">
        <div class="form-group">
            <label for="subject_id" class="col-form-label-sm">{{ __('general.subjects') }}</label>
            <select name="subject_id" id="subject_id" class="form-control form-control-sm @error('subject_id') is-invalid @enderror">
                <option value="">{{ __('general.Select Subject') }}</option>
                @foreach($subjects as $subject)
                    <option value="{{ $subject->id }}" {{ old('subject_id', $dailyChallenge?->subject_id) == $subject->id ? 'selected' : '' }}>
                        {{ $subject->name_ar }}
                    </option>
                @endforeach
            </select>
            @error('subject_id')<span class="col-form-label-sm text-danger">{{ $message }}</span>@enderror
        </div>
    </div>

    <!-- Question Arabic -->
    <div class="col-md-6 col-12">
        <div class="form-group">
            <label for="title_ar" class="col-form-label-sm">{{ __('general.Question') }} ({{ __('dataTable.name_ar') }})</label>
            <input type="text" name="title_ar" id="title_ar"
                   value="{{ old('title_ar', $dailyChallenge?->title_ar) }}"
                   class="form-control form-control-sm @error('title_ar') is-invalid @enderror" required />
            @error('title_ar')<span class="col-form-label-sm text-danger">{{ $message }}</span>@enderror
        </div>
    </div>

    <!-- Question English -->
    <div class="col-md-6 col-12">
        <div class="form-group">
            <label for="title_en" class="col-form-label-sm">{{ __('general.Question') }} ({{ __('dataTable.name_en') }})</label>
            <input type="text" name="title_en" id="title_en"
                   value="{{ old('title_en', $dailyChallenge?->title_en) }}"
                   class="form-control form-control-sm @error('title_en') is-invalid @enderror" />
            @error('title_en')<span class="col-form-label-sm text-danger">{{ $message }}</span>@enderror
        </div>
    </div>

    <!-- Reward Points -->
    <div class="col-md-4 col-12">
        <div class="form-group">
            <label for="reward_points" class="col-form-label-sm">{{ __('general.reward_points') }}</label>
            <input type="number" min="0" name="reward_points" id="reward_points"
                   value="{{ old('reward_points', $dailyChallenge?->reward_points ?? 25) }}"
                   class="form-control form-control-sm @error('reward_points') is-invalid @enderror" required />
            @error('reward_points')<span class="col-form-label-sm text-danger">{{ $message }}</span>@enderror
        </div>
    </div>

    <!-- Time Limit -->
    <div class="col-md-4 col-12">
        <div class="form-group">
            <label for="time_limit_seconds" class="col-form-label-sm">{{ __('general.time_limit_seconds') }}</label>
            <input type="number" min="5" name="time_limit_seconds" id="time_limit_seconds"
                   value="{{ old('time_limit_seconds', $dailyChallenge?->time_limit_seconds ?? 180) }}"
                   class="form-control form-control-sm @error('time_limit_seconds') is-invalid @enderror" required />
            @error('time_limit_seconds')<span class="col-form-label-sm text-danger">{{ $message }}</span>@enderror
        </div>
    </div>

    <!-- Challenge Date -->
    <div class="col-md-4 col-12">
        <div class="form-group">
            <label for="challenge_date" class="col-form-label-sm">{{ __('general.challenge_date') }}</label>
            <input type="date" name="challenge_date" id="challenge_date"
                   value="{{ $challengeDate }}"
                   class="form-control form-control-sm @error('challenge_date') is-invalid @enderror" required />
            @error('challenge_date')<span class="col-form-label-sm text-danger">{{ $message }}</span>@enderror
        </div>
    </div>

    <!-- Options -->
    <div class="col-12 mt-2">
        <label class="col-form-label-sm d-block fw-bold">{{ __('general.answers') }} ({{ __('general.Select the correct answer') }})</label>
        @error('correct_answer')<span class="col-form-label-sm text-danger d-block">{{ $message }}</span>@enderror
        @error('options')<span class="col-form-label-sm text-danger d-block">{{ $message }}</span>@enderror
    </div>

    @for($i = 0; $i < 4; $i++)
        @php $opt = $existingOptions->get($i); @endphp
        <div class="col-12">
            <div class="row align-items-center border rounded p-2 mb-2 mx-0">
                <div class="col-md-1 col-2 text-center">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="correct_answer"
                               id="correct_{{ $i }}" value="{{ $i }}" {{ $correctIndex === $i ? 'checked' : '' }} required>
                        <label class="form-check-label" for="correct_{{ $i }}">{{ $i + 1 }}</label>
                    </div>
                </div>
                <div class="col-md-6 col-12">
                    <input type="text" name="options[{{ $i }}][title_ar]"
                           value="{{ old("options.$i.title_ar", $opt?->title_ar) }}"
                           class="form-control form-control-sm @error("options.$i.title_ar") is-invalid @enderror"
                           placeholder="{{ __('general.answers') }} ({{ __('dataTable.name_ar') }})" required />
                    @error("options.$i.title_ar")<span class="col-form-label-sm text-danger">{{ $message }}</span>@enderror
                </div>
                <div class="col-md-5 col-12">
                    <input type="text" name="options[{{ $i }}][title_en]"
                           value="{{ old("options.$i.title_en", $opt?->title_en) }}"
                           class="form-control form-control-sm @error("options.$i.title_en") is-invalid @enderror"
                           placeholder="{{ __('general.answers') }} ({{ __('dataTable.name_en') }})" />
                    @error("options.$i.title_en")<span class="col-form-label-sm text-danger">{{ $message }}</span>@enderror
                </div>
            </div>
        </div>
    @endfor

    <!-- Status -->
    <div class="col-12 mt-2">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="status" id="status" value="1" {{ $statusChecked ? 'checked' : '' }}>
            <label class="form-check-label" for="status">{{ __('dataTable.status') }}</label>
        </div>
    </div>

    <!-- Submit -->
    <div class="col-12 mt-2">
        <div class="form-group">
            <button type="submit" class="btn btn-primary">{{ __('general.Save') }}</button>
        </div>
    </div>
</div>
