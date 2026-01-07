<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class WeatherController extends Controller
{
    /**
     * Get weather forecast using Tomorrow.io API
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getForecast(Request $request)
    {
        // Validate input
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 0,
                'message' => 'Validation failed',
                'data' => null,
                'errors' => $validator->errors()
            ], 400);
        }

        $latitude = $request->latitude;
        $longitude = $request->longitude;
        $apiKey = env('TOMORROW_API_KEY');
        $apiHost = env('TOMORROW_API_HOST', 'https://api.tomorrow.io');

        // Check if API key is configured
        if (empty($apiKey)) {
            return response()->json([
                'code' => 0,
                'message' => 'Weather API is not configured',
                'data' => null
            ], 500);
        }

        try {
            // Call Tomorrow.io API for weather forecast
            $response = Http::timeout(15)->get("{$apiHost}/v4/weather/forecast", [
                'location' => "{$latitude},{$longitude}",
                'timesteps' => '1d',
                'units' => 'metric',
                'apikey' => $apiKey
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // Transform Tomorrow.io response to match app's expected format
                $forecast = $this->transformTomorrowioData($data, $latitude, $longitude);
                
                return response()->json([
                    'code' => 1,
                    'message' => 'Weather forecast retrieved successfully',
                    'data' => $forecast
                ], 200);
            } else {
                \Log::error('Tomorrow.io API Error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                return response()->json([
                    'code' => 0,
                    'message' => 'Failed to fetch weather data',
                    'data' => null,
                    'error' => $response->body()
                ], $response->status());
            }
        } catch (\Exception $e) {
            \Log::error('Weather API Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'code' => 0,
                'message' => 'Error fetching weather data: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Transform Tomorrow.io API response to match app's format
     * 
     * @param array $data
     * @param float $latitude
     * @param float $longitude
     * @return array
     */
    private function transformTomorrowioData($data, $latitude, $longitude)
    {
        $location = $data['location'] ?? [];
        $timelines = $data['timelines']['daily'] ?? [];

        $forecasts = [];
        
        foreach ($timelines as $day) {
            $date = $day['time'] ?? null;
            $values = $day['values'] ?? [];

            $forecasts[] = [
                'dt' => $date ? strtotime($date) : null,
                'temp' => [
                    'day' => $values['temperatureAvg'] ?? null,
                    'min' => $values['temperatureMin'] ?? null,
                    'max' => $values['temperatureMax'] ?? null,
                ],
                'humidity' => $values['humidityAvg'] ?? null,
                'weather' => [
                    [
                        'main' => $this->mapWeatherCode($values['weatherCodeMax'] ?? 0),
                        'description' => $this->getWeatherDescription($values['weatherCodeMax'] ?? 0),
                        'icon' => $this->getWeatherIcon($values['weatherCodeMax'] ?? 0),
                    ]
                ],
                'wind_speed' => $values['windSpeedAvg'] ?? null,
                'clouds' => $values['cloudCoverAvg'] ?? null,
                'pop' => $values['precipitationProbabilityAvg'] ?? 0,
            ];
        }

        return [
            'coord' => [
                'lat' => $latitude,
                'lon' => $longitude
            ],
            'list' => $forecasts,
            'city' => [
                'name' => $location['name'] ?? 'Unknown',
                'coord' => [
                    'lat' => $latitude,
                    'lon' => $longitude
                ]
            ]
        ];
    }

    /**
     * Map Tomorrow.io weather codes to readable conditions
     * 
     * @param int $code
     * @return string
     */
    private function mapWeatherCode($code)
    {
        $weatherCodes = [
            0 => 'Unknown',
            1000 => 'Clear',
            1100 => 'Mostly Clear',
            1101 => 'Partly Cloudy',
            1102 => 'Mostly Cloudy',
            1001 => 'Cloudy',
            2000 => 'Fog',
            2100 => 'Light Fog',
            4000 => 'Drizzle',
            4001 => 'Rain',
            4200 => 'Light Rain',
            4201 => 'Heavy Rain',
            5000 => 'Snow',
            5001 => 'Flurries',
            5100 => 'Light Snow',
            5101 => 'Heavy Snow',
            6000 => 'Freezing Drizzle',
            6001 => 'Freezing Rain',
            6200 => 'Light Freezing Rain',
            6201 => 'Heavy Freezing Rain',
            7000 => 'Ice Pellets',
            7101 => 'Heavy Ice Pellets',
            7102 => 'Light Ice Pellets',
            8000 => 'Thunderstorm',
        ];

        return $weatherCodes[$code] ?? 'Unknown';
    }

    /**
     * Get weather description from code
     * 
     * @param int $code
     * @return string
     */
    private function getWeatherDescription($code)
    {
        return strtolower($this->mapWeatherCode($code));
    }

    /**
     * Get weather icon code from Tomorrow.io code
     * 
     * @param int $code
     * @return string
     */
    private function getWeatherIcon($code)
    {
        $iconMap = [
            1000 => '01d', // Clear
            1100 => '02d', // Mostly Clear
            1101 => '03d', // Partly Cloudy
            1102 => '04d', // Mostly Cloudy
            1001 => '04d', // Cloudy
            2000 => '50d', // Fog
            2100 => '50d', // Light Fog
            4000 => '09d', // Drizzle
            4001 => '10d', // Rain
            4200 => '10d', // Light Rain
            4201 => '10d', // Heavy Rain
            5000 => '13d', // Snow
            5001 => '13d', // Flurries
            5100 => '13d', // Light Snow
            5101 => '13d', // Heavy Snow
            6000 => '13d', // Freezing Drizzle
            6001 => '13d', // Freezing Rain
            6200 => '13d', // Light Freezing Rain
            6201 => '13d', // Heavy Freezing Rain
            7000 => '13d', // Ice Pellets
            7101 => '13d', // Heavy Ice Pellets
            7102 => '13d', // Light Ice Pellets
            8000 => '11d', // Thunderstorm
        ];

        return $iconMap[$code] ?? '01d';
    }
}
