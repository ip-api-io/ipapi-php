# VPN, proxy & Tor detection

Catch traffic that hides behind anonymizers. Every `lookup` already returns the
`suspicious_factors` flags for proxy, VPN, Tor, datacenter, spam and crawler; the
dedicated `torCheck` adds live Tor exit-node confirmation.

Powers [VPN detection](https://ip-api.io/vpn-detection-api),
[proxy detection](https://ip-api.io/proxy-detection-api) and
[Tor detection](https://ip-api.io/tor-detection).

## `suspicious_factors` — flags on every lookup

No extra call needed: read the flags from a normal [`lookup`](ip-geolocation.md).

```php
use IpApiIo\Client;

$client = new Client(apiKey: 'YOUR_API_KEY');

$info = $client->lookup('185.220.101.1');
$f = $info['suspicious_factors'];

var_dump($f['is_vpn']);         // VPN service
var_dump($f['is_proxy']);       // open / anonymizing proxy
var_dump($f['is_tor_node']);    // Tor node
var_dump($f['is_datacenter']);  // hosting / datacenter IP (often a bot)
var_dump($f['is_spam']);        // known spam source
var_dump($f['is_crawler']);     // known crawler / bot
var_dump($f['is_threat']);      // listed on a threat feed

if ($f['is_vpn'] || $f['is_proxy'] || $f['is_tor_node']) {
    // require step-up verification
}
```

### `suspicious_factors`

| Field | Type | Meaning |
|---|---|---|
| `is_proxy` | bool | Open or anonymizing proxy |
| `is_vpn` | bool | Commercial VPN endpoint |
| `is_tor_node` | bool | Part of the Tor network |
| `is_datacenter` | bool | Hosting / datacenter range |
| `is_spam` | bool | Known spam source |
| `is_crawler` | bool | Known crawler / bot |
| `is_threat` | bool | Listed on a threat feed |

## `torCheck(string $ip)` — confirm a Tor exit node

A dedicated check against the live Tor node list, with a count of matching nodes.

```php
$tor = $client->torCheck('185.220.101.1');

var_dump($tor['is_tor']);     // true
echo $tor['tor_node_count'];  // number of matching Tor nodes
```

### Response (`TorDetection`)

| Field | Type | Description |
|---|---|---|
| `ip` | string | The checked IP |
| `is_tor` | bool | Whether the IP is a Tor node |
| `tor_node_count` | int | Matching nodes for the IP |

> Want one number instead of individual flags? See
> [Fraud detection & risk scoring](fraud-risk-scoring.md) — `riskScore` folds all of
> these signals into a 0–100 score.

## See also

- [IP geolocation & bulk lookup](ip-geolocation.md) — where `suspicious_factors` comes from
- [Fraud detection & risk scoring](fraud-risk-scoring.md) — combine the flags into a score
- Product pages: [VPN detection](https://ip-api.io/vpn-detection-api) · [Proxy detection](https://ip-api.io/proxy-detection-api) · [Tor detection](https://ip-api.io/tor-detection)
- [Full tutorial on ip-api.io](https://ip-api.io/docs/sdk/php/vpn-proxy-tor)
