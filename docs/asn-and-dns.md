# ASN & DNS lookups

Resolve the network and DNS layer behind an IP or domain: which autonomous system
owns an address, who registered a domain, what a host's PTR record is, and which mail
servers a domain uses.

Powers [ASN lookup](https://ip-api.io/asn-lookup),
[WHOIS lookup](https://ip-api.io/whois-lookup),
[reverse DNS](https://ip-api.io/reverse-dns-lookup) and
[MX record lookup](https://ip-api.io/mx-record-lookup).

## `asn(string $ip)` — autonomous system for an IP

Returns the ASN, owning organization, network range and country for an IP — and
whether it belongs to a datacenter.

```php
use IpApiIo\Client;

$client = new Client(apiKey: 'YOUR_API_KEY');

$asn = $client->asn('8.8.8.8');

echo $asn['asn'];            // 15169
echo $asn['organization'];   // "Google LLC"
echo $asn['network'];        // "8.8.8.0/24"
var_dump($asn['is_datacenter']); // true
echo $asn['country_code'];   // "US"
```

### Response (`AsnLookup`)
`ip`, `asn`, `organization`, `network`, `is_datacenter`, `country`, `country_code`.

## `whois(string $domain)` — domain registration

WHOIS record for a domain: registrar, registration/expiry/update dates, name servers,
status codes and the raw WHOIS text.

```php
$whois = $client->whois('example.com');

echo $whois['registrar']['name'];
echo $whois['registered_on'];     // "1995-08-14"
echo $whois['expires_on'];
print_r($whois['name_servers']);  // ["a.iana-servers.net", ...]
echo $whois['status'][0]['humanized'];
```

### Response (`Whois`)
`domain`, `registrar` (`name`, `url`, `iana_id`), `registered_on`, `expires_on`,
`updated_on`, `name_servers`, `status` (`code`, `humanized`), `raw`, `error`.

## `reverseDns(string $ip)` — PTR record for an IP

```php
$rdns = $client->reverseDns('8.8.8.8');

echo $rdns['hostname'];    // "dns.google"
echo $rdns['ptr_record'];
echo $rdns['ttl'];
```

### Response (`ReverseDns`)
`ip`, `hostname`, `ptr_record`, `ttl`.

## `forwardDns(string $hostname)` — resolve a hostname to addresses

```php
$fdns = $client->forwardDns('dns.google');

foreach ($fdns['addresses'] as $record) {
    echo "{$record['type']} {$record['address']} {$record['ttl']}"; // "A" "8.8.8.8" 300
}
```

### Response (`ForwardDns`)
`hostname`, `addresses` (each `type`, `address`, `ttl`).

## `mxRecords(string $domain)` — mail servers for a domain

```php
$mx = $client->mxRecords('example.com');

foreach ($mx['mx_records'] as $record) {
    echo "{$record['priority']} {$record['hostname']} {$record['ttl']}";
}
```

### Response (`MxLookup`)
`domain`, `mx_records` (each `priority`, `hostname`, `ttl`).

## See also

- [IP geolocation & bulk lookup](ip-geolocation.md) — geolocation for the same IP
- [Email validation & verification](email-validation.md) — MX records feed deliverability
- [Domain age checker](domain-age.md) — registration age from WHOIS data
- Product pages: [ASN lookup](https://ip-api.io/asn-lookup) · [WHOIS lookup](https://ip-api.io/whois-lookup) · [Reverse DNS](https://ip-api.io/reverse-dns-lookup) · [MX record lookup](https://ip-api.io/mx-record-lookup)
- [Full tutorial on ip-api.io](https://ip-api.io/docs/sdk/php/asn-dns)
