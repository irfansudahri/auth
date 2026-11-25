-- ============================================================================
-- Sinkronisasi & Trigger: dbsiakad_stikomtb -> sso_auth
-- Jalankan di MySQL yang memiliki akses ke database dbsiakad_stikomtb & sso_auth
-- ============================================================================

-- 1. Copy data awal dari dbsiakad_stikomtb.login_siakad -> sso_auth.siakad_users
INSERT INTO sso_auth.siakad_users
    (tenant_key, userid, password, nama_lengkap, foto, level, blokir, last_login, created_at, updated_at)
SELECT
    'dbsiakad_stikomtb',
    ls.userid,
    ls.password,
    ls.nama_lengkap,
    ls.foto,
    LOWER(ls.level),
    ls.blokir,
    ls.last_login,
    NOW(),
    NOW()
FROM dbsiakad_stikomtb.login_siakad ls
ON DUPLICATE KEY UPDATE
    password     = VALUES(password),
    nama_lengkap = VALUES(nama_lengkap),
    foto         = VALUES(foto),
    level        = VALUES(level),
    blokir       = VALUES(blokir),
    last_login   = VALUES(last_login),
    updated_at   = NOW();

-- 2. Copy level utama dari login_siakad ke sso_auth.user_app_level (untuk aplikasi 'kelas')
INSERT INTO sso_auth.user_app_level
    (tenant_key, siakad_user_id, app_id, level, aktif, created_at, updated_at)
SELECT
    'dbsiakad_stikomtb',
    su.id,
    a.id,
    LOWER(su.level),
    IF(su.blokir = 'N', 1, 0),
    NOW(),
    NOW()
FROM sso_auth.siakad_users su
JOIN sso_auth.apps a ON a.kode = 'kelas'
WHERE su.tenant_key = 'dbsiakad_stikomtb'
ON DUPLICATE KEY UPDATE
    aktif      = VALUES(aktif),
    updated_at = NOW();

-- 3. Copy level tambahan dari dbsiakad_stikomtb.login_siakad_level -> sso_auth.user_app_level (aplikasi 'kelas')
INSERT INTO sso_auth.user_app_level
    (tenant_key, siakad_user_id, app_id, level, aktif, created_at, updated_at)
SELECT
    'dbsiakad_stikomtb',
    su.id,
    a.id,
    LOWER(lsl.level),
    lsl.aktif,
    COALESCE(lsl.created_at, NOW()),
    NOW()
FROM dbsiakad_stikomtb.login_siakad_level lsl
JOIN sso_auth.siakad_users su
    ON su.tenant_key = 'dbsiakad_stikomtb' AND su.userid = lsl.userid
JOIN sso_auth.apps a ON a.kode = 'kelas'
ON DUPLICATE KEY UPDATE
    aktif      = VALUES(aktif),
    updated_at = NOW();

-- 4. Triggers pada dbsiakad_stikomtb.login_siakad
DROP TRIGGER IF EXISTS dbsiakad_stikomtb.trg_login_siakad_after_insert;
DROP TRIGGER IF EXISTS dbsiakad_stikomtb.trg_login_siakad_after_update;

DELIMITER $$

CREATE TRIGGER dbsiakad_stikomtb.trg_login_siakad_after_insert
AFTER INSERT ON dbsiakad_stikomtb.login_siakad
FOR EACH ROW
BEGIN
    DECLARE v_user_id BIGINT UNSIGNED;
    DECLARE v_app_id BIGINT UNSIGNED;

    INSERT INTO sso_auth.siakad_users
        (tenant_key, userid, password, nama_lengkap, foto, level, blokir, last_login, created_at, updated_at)
    VALUES
        ('dbsiakad_stikomtb', NEW.userid, NEW.password, NEW.nama_lengkap, NEW.foto, LOWER(NEW.level), NEW.blokir, NEW.last_login, NOW(), NOW())
    ON DUPLICATE KEY UPDATE
        password     = VALUES(password),
        nama_lengkap = VALUES(nama_lengkap),
        foto         = VALUES(foto),
        level        = VALUES(level),
        blokir       = VALUES(blokir),
        last_login   = VALUES(last_login),
        updated_at   = NOW();

    SELECT id INTO v_user_id FROM sso_auth.siakad_users
    WHERE tenant_key = 'dbsiakad_stikomtb' AND userid = NEW.userid LIMIT 1;

    SELECT id INTO v_app_id FROM sso_auth.apps WHERE kode = 'kelas' LIMIT 1;

    IF v_user_id IS NOT NULL AND v_app_id IS NOT NULL THEN
        INSERT INTO sso_auth.user_app_level
            (tenant_key, siakad_user_id, app_id, level, aktif, created_at, updated_at)
        VALUES
            ('dbsiakad_stikomtb', v_user_id, v_app_id, LOWER(NEW.level), IF(NEW.blokir = 'N', 1, 0), NOW(), NOW())
        ON DUPLICATE KEY UPDATE
            aktif      = VALUES(aktif),
            updated_at = NOW();
    END IF;
END$$

CREATE TRIGGER dbsiakad_stikomtb.trg_login_siakad_after_update
AFTER UPDATE ON dbsiakad_stikomtb.login_siakad
FOR EACH ROW
BEGIN
    DECLARE v_user_id BIGINT UNSIGNED;
    DECLARE v_app_id BIGINT UNSIGNED;

    INSERT INTO sso_auth.siakad_users
        (tenant_key, userid, password, nama_lengkap, foto, level, blokir, last_login, created_at, updated_at)
    VALUES
        ('dbsiakad_stikomtb', NEW.userid, NEW.password, NEW.nama_lengkap, NEW.foto, LOWER(NEW.level), NEW.blokir, NEW.last_login, NOW(), NOW())
    ON DUPLICATE KEY UPDATE
        password     = VALUES(password),
        nama_lengkap = VALUES(nama_lengkap),
        foto         = VALUES(foto),
        level        = VALUES(level),
        blokir       = VALUES(blokir),
        last_login   = VALUES(last_login),
        updated_at   = NOW();

    SELECT id INTO v_user_id FROM sso_auth.siakad_users
    WHERE tenant_key = 'dbsiakad_stikomtb' AND userid = NEW.userid LIMIT 1;

    SELECT id INTO v_app_id FROM sso_auth.apps WHERE kode = 'kelas' LIMIT 1;

    IF v_user_id IS NOT NULL AND v_app_id IS NOT NULL THEN
        INSERT INTO sso_auth.user_app_level
            (tenant_key, siakad_user_id, app_id, level, aktif, created_at, updated_at)
        VALUES
            ('dbsiakad_stikomtb', v_user_id, v_app_id, LOWER(NEW.level), IF(NEW.blokir = 'N', 1, 0), NOW(), NOW())
        ON DUPLICATE KEY UPDATE
            aktif      = VALUES(aktif),
            updated_at = NOW();
    END IF;
END$$

-- 5. Triggers pada dbsiakad_stikomtb.login_siakad_level
DROP TRIGGER IF EXISTS dbsiakad_stikomtb.trg_login_siakad_level_after_insert$$
DROP TRIGGER IF EXISTS dbsiakad_stikomtb.trg_login_siakad_level_after_update$$

CREATE TRIGGER dbsiakad_stikomtb.trg_login_siakad_level_after_insert
AFTER INSERT ON dbsiakad_stikomtb.login_siakad_level
FOR EACH ROW
BEGIN
    DECLARE v_user_id BIGINT UNSIGNED;
    DECLARE v_app_id BIGINT UNSIGNED;

    SELECT id INTO v_user_id FROM sso_auth.siakad_users
    WHERE tenant_key = 'dbsiakad_stikomtb' AND userid = NEW.userid LIMIT 1;

    SELECT id INTO v_app_id FROM sso_auth.apps WHERE kode = 'kelas' LIMIT 1;

    IF v_user_id IS NOT NULL AND v_app_id IS NOT NULL THEN
        INSERT INTO sso_auth.user_app_level
            (tenant_key, siakad_user_id, app_id, level, aktif, created_at, updated_at)
        VALUES
            ('dbsiakad_stikomtb', v_user_id, v_app_id, LOWER(NEW.level), NEW.aktif, NOW(), NOW())
        ON DUPLICATE KEY UPDATE
            aktif      = VALUES(aktif),
            updated_at = NOW();
    END IF;
END$$

CREATE TRIGGER dbsiakad_stikomtb.trg_login_siakad_level_after_update
AFTER UPDATE ON dbsiakad_stikomtb.login_siakad_level
FOR EACH ROW
BEGIN
    DECLARE v_user_id BIGINT UNSIGNED;
    DECLARE v_app_id BIGINT UNSIGNED;

    SELECT id INTO v_user_id FROM sso_auth.siakad_users
    WHERE tenant_key = 'dbsiakad_stikomtb' AND userid = NEW.userid LIMIT 1;

    SELECT id INTO v_app_id FROM sso_auth.apps WHERE kode = 'kelas' LIMIT 1;

    IF v_user_id IS NOT NULL AND v_app_id IS NOT NULL THEN
        INSERT INTO sso_auth.user_app_level
            (tenant_key, siakad_user_id, app_id, level, aktif, created_at, updated_at)
        VALUES
            ('dbsiakad_stikomtb', v_user_id, v_app_id, LOWER(NEW.level), NEW.aktif, NOW(), NOW())
        ON DUPLICATE KEY UPDATE
            aktif      = VALUES(aktif),
            updated_at = NOW();
    END IF;
END$$

DELIMITER ;
