<?php
namespace GoogleIndexing;

use DeferredUpdates;

/**
 * Developed with <3 by Mippedia Community
 * Website: https://mippediacommunity.site
 */
class Hooks {

    /**
     * Terpicu saat halaman baru diterbitkan atau halaman lama disunting.
     */
    public function onPageSaveComplete( $wikiPage, $user, $summary, $flags, $revisionRecord, $editResult ) {
        $title = $wikiPage->getTitle();
        if ( $title->getNamespace() !== NS_MAIN ) return;

        $url = $title->getFullURL();
        
        DeferredUpdates::addCallableUpdate( function() use ( $url ) {
            self::pingGoogleAPI( $url, 'URL_UPDATED' );
        } );
    }

    /**
     * Terpicu saat halaman dihapus dari Wiki.
     */
    public function onPageDeleteComplete( $wikiPage, $tracker, $single, $revisionRecord, $status ) {
        $title = $wikiPage->getTitle();
        if ( $title->getNamespace() !== NS_MAIN ) return;

        $url = $title->getFullURL();

        DeferredUpdates::addCallableUpdate( function() use ( $url ) {
            self::pingGoogleAPI( $url, 'URL_DELETED' );
        } );
    }

    /**
     * Otentikasi JWT & Pengiriman HTTP POST ke Google Indexing API (Silent Mode)
     */
    private static function pingGoogleAPI( $url, $actionType ) {
        global $wgGoogleIndexingJsonPath;

        if ( empty( $wgGoogleIndexingJsonPath ) || !file_exists( $wgGoogleIndexingJsonPath ) ) {
            return;
        }

        $jsonKey = json_decode( file_get_contents( $wgGoogleIndexingJsonPath ), true );
        if ( !$jsonKey ) {
            return;
        }

        // Pembuatan Token Akses OAuth2 via JWT
        $header = json_encode( ['alg' => 'RS256', 'typ' => 'JWT'] );
        $now = time();
        $payload = json_encode( [
            'iss' => $jsonKey['client_email'],
            'scope' => 'https://www.googleapis.com/auth/indexing',
            'aud' => 'https://oauth2.googleapis.com/token',
            'exp' => $now + 3600,
            'iat' => $now
        ] );

        $b64Header = str_replace( ['+', '/', '='], ['-', '_', ''], base64_encode( $header ) );
        $b64Payload = str_replace( ['+', '/', '='], ['-', '_', ''], base64_encode( $payload ) );
        openssl_sign( $b64Header . "." . $b64Payload, $signature, $jsonKey['private_key'], OPENSSL_ALGO_SHA256 );
        $b64Signature = str_replace( ['+', '/', '='], ['-', '_', ''], base64_encode( $signature ) );
        $jwt = $b64Header . "." . $b64Payload . "." . $b64Signature;

        // Minta Token Akses dari Google
        $ch = curl_init( 'https://oauth2.googleapis.com/token' );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_POST, true );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt
        ] ) );
        $tokenData = json_decode( curl_exec( $ch ), true );
        curl_close( $ch );

        if ( !isset( $tokenData['access_token'] ) ) {
            return;
        }

        // Kirim Notifikasi ke Google Indexing API
        $ch2 = curl_init( 'https://indexing.googleapis.com/v3/urlNotifications:publish' );
        curl_setopt( $ch2, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch2, CURLOPT_POST, true );
        curl_setopt( $ch2, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $tokenData['access_token']
        ] );
        curl_setopt( $ch2, CURLOPT_POSTFIELDS, json_encode( [
            'url' => $url,
            'type' => $actionType
        ] ) );
        curl_exec( $ch2 );
        curl_close( $ch2 );
    }
}
