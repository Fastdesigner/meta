# Meta

fiCMS dependency plugin for Meta OAuth provider metadata and reusable Graph API helpers.

## OAuth

The plugin exposes OAuth metadata for the shared fiCMS OAuth plugin:

```text
oauth/provider/meta.json
```

Site-specific credentials stay in:

```text
system/plugins/oauth/clients/meta.json
```

The provider uses the central fiCMS redirect:

```text
https://fastdesign.de/oauth.php?action=callback
```

## Scopes

```php
$reviewScopes = \meta\Scopes::reviews();
$leadScopes = \meta\Scopes::leads();
```

The default provider scopes cover Page selection and Page recommendations. Leads can be added through `\meta\Scopes::leads()` when the consuming plugin needs lead retrieval.

## Graph

```php
$meta = new \meta\Meta('default');
$pages = $meta->pages();
$token = $meta->pageAccessToken('123456789');
$ratings = $meta->ratings('123456789',$token);
```

Consumers store selected Page IDs, not Page access tokens. The helper resolves the Page access token from the connected CMS OAuth account when needed.
