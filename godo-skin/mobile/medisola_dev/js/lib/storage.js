var storage = {
	// iOS 사파리내 개인정보보호(시크릿) 모드에서 로컬스토리지 에러 발생해서 해당 부분 예외 처리
	isLocalStorageNameSupported: function () {
		var localStorageName = 'localStorage',
			testKey = 'test',
			storage = window.sessionStorage;
		try {
			storage.setItem(testKey, '1');
			storage.removeItem(testKey);
			return localStorageName in window && window[localStorageName];
		}
		catch (error) {
			return false;
		}
	},
	saveSession: function(control_key, control_value) {
		if (!this.isLocalStorageNameSupported()) {
			gd_create_cookie(control_key, control_value, 7);
		} else {
			window.sessionStorage[control_key] = control_value;
		}
	},
	loadSession: function(control_key) {
		var control_value;
		if (!this.isLocalStorageNameSupported()) {
			control_value = gd_read_cookie(control_key);
		} else {
			control_value = sessionStorage[control_key];
		}
		return control_value;
	},
	saveVal: function(control_key, control_value) {
		if (!this.isLocalStorageNameSupported()) {
			this.createCookie(control_key, control_value, 7);
		} else {
			window.localStorage.setItem(control_key, control_value);
		}
	},
	loadVal: function(control_key) {
		var control_value;
		if (!this.isLocalStorageNameSupported()) {
			control_value = this.readCookie(control_key);
		} else {
			control_value = localStorage.getItem(control_key);
		}
		return control_value;
	},
	createCookie: function(name, value, days) {
		if (days) {
			var date = new Date();
			date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
			var expires = "; expires=" + date.toGMTString();
		} else
			var expires = "";
		document.cookie = name + "=" + value  + "; path=/; expires=" + expires + ";";
	},
	readCookie: function(name) {
		var result = "";
		var nameEQ = name + "=";
		var ca = document.cookie.split(';');
		for (var i = 0; i < ca.length; i++) {
			var c = ca[i];
			while (c.charAt(0) == ' ') c = c.substring(1, c.length);
			if (c.indexOf(nameEQ) == 0) {
				result = c.substring(nameEQ.length, c.length);
			}
		}
		return result;
	}
};
