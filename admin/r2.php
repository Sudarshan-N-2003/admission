function download_from_r2(string $key): string {
    $s3 = r2_client();

    $tmp = sys_get_temp_dir() . '/' . uniqid() . '_' . basename($key);

    $s3->getObject([
        'Bucket' => getenv('R2_BUCKET'),
        'Key'    => $key,
        'SaveAs'=> $tmp
    ]);

    return $tmp;
}