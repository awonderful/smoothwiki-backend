<?php

namespace App\Services;

class SystemService {
		public function info() {
			return [
				'appName' => config('app.name'),
			];
		}
}