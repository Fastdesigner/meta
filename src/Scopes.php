<?php

namespace meta;

class Scopes {
	public static function reviews() {
		return ['pages_show_list','pages_read_engagement','pages_read_user_content'];
	}

	public static function leads() {
		return array_values(array_unique(array_merge(self::reviews(),['leads_retrieval'])));
	}

	public static function marketing() {
		return array_values(array_unique(array_merge(self::leads(),['ads_management','business_management'])));
	}
}
