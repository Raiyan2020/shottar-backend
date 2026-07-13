<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardTeacherController extends Controller
{
    public function index(){

        $teacher = auth('admin')->user();

        $subjects = Subject::whereHas('teachers', function($q) use ($teacher) {
            $q->where('teacher_id', $teacher->id);
        })->where('status', 1)->get();

        $usersCount = User::whereHas('orders.items', function($q) use ($subjects) {
            $q->whereIn('subject_id', $subjects->pluck('id'));
        })->count();

        $subjectsCount = $subjects->count();


        $courseMaterialsCount = DB::table('course_materials')
            ->whereIn('subject_id', $subjects->pluck('id')) // بس مواد المدرّس
            ->where('status', 1)
            ->where('type', 'lesson')
            ->count();


//        $totalAmount = Order::where('status', '1')->sum('total');


        $lastWeek = $this->getLastWeekLabels();
        $datasets = $this->generateDatasets($lastWeek);

        $chartOptions = [
            'responsive' => true,
            'legend' => [
                'display' => true,
                'labels' => [
                    'fontColor' => '#333',
                    'fontSize' => 14,
                ],
            ],
            'scales' => [
                'xAxes' => [[
                    'ticks' => [
                        'fontColor' => '#333',
                    ],
                ]],
                'yAxes' => [[
                    'ticks' => [
                        'beginAtZero' => true,
                        'fontColor' => '#333',
                    ],
                ]],
            ],
            'animation' => [
                'duration' => 2500,
            ],
            'elements' => [
                'line' => [
                    'borderWidth' => 2, // Adjust the thickness of the lines
                ],
                'point' => [
                    'radius' => 4, // Adjust the size of the data points
                    'hoverRadius' => 6,
                ],
            ],
            'cubicInterpolationMode' => 'default', // Use 'default' or 'monotone'
        ];


        $lineChart = $this->createChart('lineChart', 'line', $lastWeek, $datasets, $chartOptions);

//        $menu = SideMenu::where('side_id',null)->with('side')->get();
//        $sequences =SideMenu::where('side_id', null)->lazy();

        return view('dashboard.dashboard',compact( 'lineChart','usersCount','subjectsCount','courseMaterialsCount'));
    }

    private function createChart($name, $type, $labels, $datasets, $options)
    {
        return app()->chartjs
            ->name($name)
            ->type($type)
            ->size(['width' => 800, 'height' => 320])
            ->labels($labels)
            ->datasets($datasets)
            ->options($options);
    }

    private function getLastWeekLabels()
    {
        $lastWeek = collect([]);
        $labels = [];

        for ($i = 6; $i >= 0; $i--) {
            $day = now()->subDays($i);
            $lastWeek->push($day->format('l'));
            $labels[] = $day->format('F j');
        }

        return $labels;
    }

    private function generateDatasets($labels)
    {
        $datasets = [];

        for ($i = 6; $i >= 0; $i--) {
            $day = now()->subDays($i);
            $startDate = $day->copy()->startOfDay();
            $endDate = $day->copy()->endOfDay();


            $teacher = auth('admin')->user();

            $subjectIds = Subject::whereHas('teachers', function($q) use ($teacher) {
                $q->where('teacher_id', $teacher->id);
            })
                ->pluck('id');

            $materialIds = DB::table('course_materials')
                ->whereIn('subject_id', $subjectIds)
                ->pluck('id');

            $customers = DB::table('users')
                ->whereIn('id', function($q) use ($subjectIds) {
                    $q->select('user_id')
                        ->from('orders')
                        ->join('order_items', 'orders.id', '=', 'order_items.order_id')
                        ->whereIn('order_items.subject_id', $subjectIds);
                })
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();

            $MaterialView = DB::table('material_views')
                ->whereIn('course_material_id', $materialIds)
                ->whereBetween('viewed_at', [$startDate, $endDate])
                ->count();


            $usersDataset[] = $customers;
            $MaterialViewDataset[] = $MaterialView;



        }

        $datasets[] = [
            "label" => __('users'),
            'backgroundColor' => "#0162e8",
            'borderColor' => "#0162e8",
            "pointBorderColor" => "#0162e8",
            "pointBackgroundColor" => "#fff",
            "pointHoverBackgroundColor" => "#fff",
            "pointHoverBorderColor" => "#0162e8",
            'data' => $usersDataset,
            'fill'=> false,
            'tension'=> 0.3
        ];

        $datasets[] = [
            "label" => __('Views'),
            'backgroundColor' => "#f95374",
            'borderColor' => "#f95374",
            "pointBorderColor" => "#f95374",
            "pointBackgroundColor" => "#fff",
            "pointHoverBackgroundColor" => "#fff",
            "pointHoverBorderColor" => "#f95374",
            'data' => $MaterialViewDataset,
            'fill'=> false,
            'tension'=> 0.3
        ];

        return $datasets;
    }

}
