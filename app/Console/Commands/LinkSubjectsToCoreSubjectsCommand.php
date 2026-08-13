<?php

namespace App\Console\Commands;

use App\Models\CoreSubject;
use App\Models\Subject;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class LinkSubjectsToCoreSubjectsCommand extends Command
{
    protected $signature = 'subjects:link-core
                            {--dry-run : Preview matches without writing to the database}
                            {--no-create : Do not create missing core subjects; only link exact matches}';

    protected $description = 'Link existing subjects to core subjects by matching Arabic/English names (creates missing core subjects by default)';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $createMissing = ! (bool) $this->option('no-create');

        $subjects = Subject::query()
            ->whereNull('core_subject_id')
            ->orderBy('id')
            ->get();

        if ($subjects->isEmpty()) {
            $this->info('No unlinked subjects found.');

            return self::SUCCESS;
        }

        $this->info("Found {$subjects->count()} subject(s) without core_subject_id.");
        if ($dryRun) {
            $this->warn('Dry run — no changes will be saved.');
        }

        $linked = 0;
        $created = 0;
        $skipped = 0;

        // cache existing core subjects by normalized names
        $coreByKey = [];
        foreach (CoreSubject::query()->orderBy('id')->get() as $core) {
            foreach ($this->keysFor($core->name_ar, $core->name_en) as $key) {
                $coreByKey[$key] = $core;
            }
        }

        DB::beginTransaction();

        try {
            // First pass: link to existing core subjects
            $remaining = collect();
            foreach ($subjects as $subject) {
                $match = $this->findCore($subject, $coreByKey);
                if ($match) {
                    $this->line("  [link] Subject #{$subject->id} «{$subject->name_ar}» → Core #{$match->id} «{$match->name_ar}»");
                    if (! $dryRun) {
                        $this->attach($subject, $match);
                    }
                    $linked++;
                } else {
                    $remaining->push($subject);
                }
            }

            // Second pass: create missing core subjects from unique names
            if ($createMissing && $remaining->isNotEmpty()) {
                $groups = $remaining->groupBy(function (Subject $subject) {
                    return $this->normalize($subject->name_ar)
                        ?: $this->normalize($subject->name_en)
                        ?: 'subject-'.$subject->id;
                });

                foreach ($groups as $key => $group) {
                    /** @var Subject $first */
                    $first = $group->first();
                    $nameAr = trim((string) $first->name_ar) ?: (trim((string) $first->name_en) ?: 'Subject');
                    $nameEn = trim((string) $first->name_en) ?: $nameAr;

                    $this->line("  [create] Core «{$nameAr}» / «{$nameEn}» for ".$group->count().' subject(s)');

                    if ($dryRun) {
                        $created++;
                        $linked += $group->count();
                        continue;
                    }

                    $core = CoreSubject::create([
                        'name_ar' => $nameAr,
                        'name_en' => $nameEn,
                        'image' => $first->getRawOriginal('image') ?? $first->image,
                        'status' => true,
                    ]);
                    $created++;

                    foreach ($this->keysFor($core->name_ar, $core->name_en) as $coreKey) {
                        $coreByKey[$coreKey] = $core;
                    }

                    foreach ($group as $subject) {
                        $this->attach($subject, $core);
                        $linked++;
                        $this->line("    → Subject #{$subject->id} linked to Core #{$core->id}");
                    }
                }
            } elseif ($remaining->isNotEmpty()) {
                foreach ($remaining as $subject) {
                    $this->warn("  [skip] Subject #{$subject->id} «{$subject->name_ar}» — no matching core subject");
                    $skipped++;
                }
            }

            if ($dryRun) {
                DB::rollBack();
            } else {
                DB::commit();
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->info("Done. linked={$linked}, core_created={$created}, skipped={$skipped}".($dryRun ? ' (dry-run)' : ''));

        return self::SUCCESS;
    }

    protected function attach(Subject $subject, CoreSubject $core): void
    {
        $subject->core_subject_id = $core->id;
        $subject->name_ar = $core->name_ar;
        $subject->name_en = $core->name_en ?: $core->name_ar;
        $subject->image = $core->getRawOriginal('image') ?? $core->image;
        $subject->save();
    }

    protected function findCore(Subject $subject, array $coreByKey): ?CoreSubject
    {
        foreach ($this->keysFor($subject->name_ar, $subject->name_en) as $key) {
            if (isset($coreByKey[$key])) {
                return $coreByKey[$key];
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    protected function keysFor(?string $nameAr, ?string $nameEn): array
    {
        $keys = [];
        foreach ([$nameAr, $nameEn] as $name) {
            $normalized = $this->normalize($name);
            if ($normalized !== '') {
                $keys[] = $normalized;
            }
        }

        return array_values(array_unique($keys));
    }

    protected function normalize(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        // collapse whitespace + lowercase for EN; Arabic stays as-is after trim/collapse
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return mb_strtolower($value, 'UTF-8');
    }
}
