<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Post;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(){

        $usersCount = User::count();
        $subjectsCount = Subject::where('status', '1')->count();
        $courseMaterialsCount = DB::table('course_materials')->where('status', '1')
            ->where('type','lesson')
            ->count();
        $totalAmount = Order::where('status', '1')->sum('total');


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

        return view('dashboard.dashboard',compact( 'lineChart','usersCount','subjectsCount','courseMaterialsCount', 'totalAmount'));
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

            $customers = DB::table('users')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();

            $MaterialView = DB::table('material_views')
                ->whereBetween('viewed_at', [$startDate, $endDate])
                ->count();

            //ordersum
             $orderSum = DB::table('orders')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->where('status', '1')
                ->sum('total');




            $usersDataset[] = $customers;
            $MaterialViewDataset[] = $MaterialView;
            $orderSumDataset[] = $orderSum;



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
        $datasets[] = [
            "label" => __('Orders'),
            'backgroundColor' => "#f9c74f",
            'borderColor' => "#f9c74f",
            "pointBorderColor" => "#f9c74f",
            "pointBackgroundColor" => "#fff",
            "pointHoverBackgroundColor" => "#fff",
            "pointHoverBorderColor" => "#f9c74f",
            'data' => $orderSumDataset,
            'fill'=> false,
            'tension'=> 0.3
        ];


        return $datasets;
    }

}
