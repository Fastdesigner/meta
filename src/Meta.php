<?php

namespace meta;

class Meta {
	protected $accountRef = 'default';
	protected $endpoint = 'https://graph.facebook.com/v25.0';
	protected $last = [];

	public function __construct($accountRef = 'default') {
		$this->accountRef = trim((string) $accountRef) !== '' ? trim((string) $accountRef) : 'default';
	}

	public function last() {
		return $this->last;
	}

	public function pages($fields = 'id,name,access_token,tasks,category', $limit = 100, $after = '') {
		$query = ['fields'=>$fields,'limit'=>max(1,min(100,intval($limit)))];
		if (trim((string) $after) !== '') $query['after'] = trim((string) $after);
		return $this->request('/me/accounts',$query);
	}

	public function ratings($pageId, $pageAccessToken, $fields = 'created_time,recommendation_type,review_text,reviewer{id,name},open_graph_story{id,message,permalink_url,from{id,name}}', $limit = 100, $after = '') {
		$pageId = $this->graphId($pageId);
		$pageAccessToken = trim((string) $pageAccessToken);
		if ($pageId == '' || $pageAccessToken == '') return false;
		$query = ['fields'=>$fields,'limit'=>max(1,min(100,intval($limit)))];
		if (trim((string) $after) !== '') $query['after'] = trim((string) $after);
		return $this->request('/'.$pageId.'/ratings',$query,'GET',[],$pageAccessToken);
	}

	public function leadForms($pageId, $pageAccessToken, $fields = 'id,name,status,created_time,leads_count', $limit = 100, $after = '') {
		$pageId = $this->graphId($pageId);
		$pageAccessToken = trim((string) $pageAccessToken);
		if ($pageId == '' || $pageAccessToken == '') return false;
		$query = ['fields'=>$fields,'limit'=>max(1,min(100,intval($limit)))];
		if (trim((string) $after) !== '') $query['after'] = trim((string) $after);
		return $this->request('/'.$pageId.'/leadgen_forms',$query,'GET',[],$pageAccessToken);
	}

	public function lead($leadId, $pageAccessToken, $fields = 'id,created_time,field_data,form_id,page_id,ad_id,adset_id,campaign_id,platform') {
		$leadId = $this->graphId($leadId);
		$pageAccessToken = trim((string) $pageAccessToken);
		if ($leadId == '' || $pageAccessToken == '') return false;
		return $this->request('/'.$leadId,['fields'=>$fields],'GET',[],$pageAccessToken);
	}

	public function subscribeLeadgen($pageId, $pageAccessToken) {
		$pageId = $this->graphId($pageId);
		$pageAccessToken = trim((string) $pageAccessToken);
		if ($pageId == '' || $pageAccessToken == '') return false;
		return $this->request('/'.$pageId.'/subscribed_apps',[],'POST',['subscribed_fields'=>'leadgen'],$pageAccessToken);
	}

	public function unsubscribeLeadgen($pageId, $pageAccessToken) {
		$pageId = $this->graphId($pageId);
		$pageAccessToken = trim((string) $pageAccessToken);
		if ($pageId == '' || $pageAccessToken == '') return false;
		return $this->request('/'.$pageId.'/subscribed_apps',[],'DELETE',['subscribed_fields'=>'leadgen'],$pageAccessToken);
	}

	public function pageAccessToken($pageId) {
		$pageId = $this->graphId($pageId);
		if ($pageId == '') return '';
		$after = '';
		do {
			$response = $this->pages('id,name,access_token,tasks',100,$after);
			if (!is_array($response)) return '';
			foreach ($response['data'] ?? [] as $page) {
				if (($page['id'] ?? '') == $pageId) return trim((string) ($page['access_token'] ?? ''));
			}
			$after = trim((string) ($response['paging']['cursors']['after'] ?? ''));
		} while ($after != '');
		return '';
	}

	protected function request($path, $query = [], $method = 'GET', $payload = [], $accessToken = '') {
		$this->last = ['result'=>false,'code'=>0,'body'=>[],'error'=>''];
		$headers = ['Accept: application/json'];
		if (trim((string) $accessToken) !== '') {
			$headers[] = 'Authorization: Bearer '.trim((string) $accessToken);
		} else {
			$auth = \oauth\OAuth::get_request('meta',$this->accountRef);
			if (!$auth) {
				$this->last['error'] = \oauth\OAuth::last_error() != '' ? \oauth\OAuth::last_error() : 'oauth_unavailable';
				return false;
			}
			$headers = array_merge($headers,$auth['headers']);
		}
		if (!empty($payload) && is_array($payload)) {
			$headers[] = 'Content-Type: application/x-www-form-urlencoded';
			$payload = http_build_query($payload);
		}
		$url = filter_var($path,FILTER_VALIDATE_URL) ? $path : rtrim($this->endpoint,'/').'/'.ltrim((string) $path,'/');
		if (!empty($query)) $url .= (strpos($url,'?') === false ? '?' : '&').http_build_query($query);
		$result = \curl__request($url,$headers,$payload,'','','',$method);
		if ($result === false) {
			$this->last['error'] = 'request_failed';
			return false;
		}
		$this->last['code'] = intval($result['code'] ?? 0);
		$this->last['error'] = trim((string) ($result['error'] ?? ''));
		$this->last['body'] = json_decode($result['body'] ?? '',true);
		if (!is_array($this->last['body'])) $this->last['body'] = [];
		if (isset($this->last['body']['error']['message'])) $this->last['error'] = trim((string) $this->last['body']['error']['message']);
		$this->last['result'] = $result !== false && $this->last['error'] == '' && $this->last['code'] >= 200 && $this->last['code'] < 300;
		return $this->last['result'] ? $this->last['body'] : false;
	}

	protected function graphId($id) {
		$id = preg_replace('/[^0-9]/','',trim((string) $id));
		return $id ?: '';
	}
}
