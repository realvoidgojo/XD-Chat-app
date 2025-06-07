-- XD Chat App Database Schema - PostgreSQL Version
-- Converted from MySQL to PostgreSQL
-- Version: 2.0.0
-- Created: 2024

-- Drop existing tables if they exist (use with caution)
-- DROP TABLE IF EXISTS activity_logs CASCADE;
-- DROP TABLE IF EXISTS notifications CASCADE;
-- DROP TABLE IF EXISTS user_settings CASCADE;
-- DROP TABLE IF EXISTS file_uploads CASCADE;
-- DROP TABLE IF EXISTS room_members CASCADE;
-- DROP TABLE IF EXISTS chat_rooms CASCADE;
-- DROP TABLE IF EXISTS login_attempts CASCADE;
-- DROP TABLE IF EXISTS user_sessions CASCADE;
-- DROP TABLE IF EXISTS messages CASCADE;
-- DROP TABLE IF EXISTS users CASCADE;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    unique_id INTEGER NOT NULL UNIQUE,
    fname VARCHAR(255) NOT NULL,
    lname VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    img VARCHAR(400) DEFAULT NULL,
    status VARCHAR(255) NOT NULL DEFAULT 'Offline now',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL DEFAULT NULL,
    last_activity TIMESTAMP NULL DEFAULT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    is_verified BOOLEAN NOT NULL DEFAULT FALSE,
    verification_token VARCHAR(255) DEFAULT NULL,
    reset_token VARCHAR(255) DEFAULT NULL,
    reset_token_expires TIMESTAMP NULL DEFAULT NULL
);

-- Create indexes for users table
CREATE INDEX IF NOT EXISTS idx_users_status ON users(status);
CREATE INDEX IF NOT EXISTS idx_users_last_activity ON users(last_activity);
CREATE INDEX IF NOT EXISTS idx_users_created_at ON users(created_at);
CREATE INDEX IF NOT EXISTS idx_users_email_status ON users(email, status);

-- Messages table
CREATE TABLE IF NOT EXISTS messages (
    msg_id SERIAL PRIMARY KEY,
    incoming_msg_id INTEGER NOT NULL,
    outgoing_msg_id INTEGER NOT NULL,
    msg TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    is_read BOOLEAN NOT NULL DEFAULT FALSE,
    is_deleted BOOLEAN NOT NULL DEFAULT FALSE,
    message_type VARCHAR(20) NOT NULL DEFAULT 'text',
    attachment_path VARCHAR(500) DEFAULT NULL,
    reply_to_msg_id INTEGER DEFAULT NULL,
    CONSTRAINT chk_message_type CHECK (message_type IN ('text', 'image', 'file', 'system')),
    FOREIGN KEY (incoming_msg_id) REFERENCES users(unique_id) ON DELETE CASCADE,
    FOREIGN KEY (outgoing_msg_id) REFERENCES users(unique_id) ON DELETE CASCADE,
    FOREIGN KEY (reply_to_msg_id) REFERENCES messages(msg_id) ON DELETE SET NULL
);

-- Create indexes for messages table
CREATE INDEX IF NOT EXISTS idx_messages_conversation ON messages(incoming_msg_id, outgoing_msg_id);
CREATE INDEX IF NOT EXISTS idx_messages_outgoing ON messages(outgoing_msg_id);
CREATE INDEX IF NOT EXISTS idx_messages_incoming ON messages(incoming_msg_id);
CREATE INDEX IF NOT EXISTS idx_messages_created_at ON messages(created_at);
CREATE INDEX IF NOT EXISTS idx_messages_is_read ON messages(is_read);
CREATE INDEX IF NOT EXISTS idx_messages_is_deleted ON messages(is_deleted);
CREATE INDEX IF NOT EXISTS idx_messages_reply_to ON messages(reply_to_msg_id);
CREATE INDEX IF NOT EXISTS idx_messages_conversation_date ON messages(incoming_msg_id, outgoing_msg_id, created_at);
CREATE INDEX IF NOT EXISTS idx_messages_unread ON messages(incoming_msg_id, is_read);

-- User sessions table (for enhanced security)
CREATE TABLE IF NOT EXISTS user_sessions (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL,
    session_id VARCHAR(255) NOT NULL UNIQUE,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    FOREIGN KEY (user_id) REFERENCES users(unique_id) ON DELETE CASCADE
);

-- Create indexes for user_sessions table
CREATE INDEX IF NOT EXISTS idx_user_sessions_user_id ON user_sessions(user_id);
CREATE INDEX IF NOT EXISTS idx_user_sessions_expires_at ON user_sessions(expires_at);
CREATE INDEX IF NOT EXISTS idx_user_sessions_is_active ON user_sessions(is_active);

-- Login attempts table (for rate limiting)
CREATE TABLE IF NOT EXISTS login_attempts (
    id SERIAL PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT DEFAULT NULL,
    success BOOLEAN NOT NULL DEFAULT FALSE,
    attempted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Create indexes for login_attempts table
CREATE INDEX IF NOT EXISTS idx_login_attempts_email ON login_attempts(email);
CREATE INDEX IF NOT EXISTS idx_login_attempts_ip_address ON login_attempts(ip_address);
CREATE INDEX IF NOT EXISTS idx_login_attempts_attempted_at ON login_attempts(attempted_at);
CREATE INDEX IF NOT EXISTS idx_login_attempts_success ON login_attempts(success);

-- Chat rooms table (for group chats - future feature)
CREATE TABLE IF NOT EXISTS chat_rooms (
    id SERIAL PRIMARY KEY,
    room_name VARCHAR(255) NOT NULL,
    room_type VARCHAR(20) NOT NULL DEFAULT 'private',
    created_by INTEGER NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    max_members INTEGER DEFAULT 50,
    room_description TEXT DEFAULT NULL,
    room_image VARCHAR(400) DEFAULT NULL,
    CONSTRAINT chk_room_type CHECK (room_type IN ('private', 'group', 'public')),
    FOREIGN KEY (created_by) REFERENCES users(unique_id) ON DELETE CASCADE
);

-- Create indexes for chat_rooms table
CREATE INDEX IF NOT EXISTS idx_chat_rooms_created_by ON chat_rooms(created_by);
CREATE INDEX IF NOT EXISTS idx_chat_rooms_room_type ON chat_rooms(room_type);
CREATE INDEX IF NOT EXISTS idx_chat_rooms_is_active ON chat_rooms(is_active);

-- Room members table (for group chats - future feature)
CREATE TABLE IF NOT EXISTS room_members (
    id SERIAL PRIMARY KEY,
    room_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'member',
    joined_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    CONSTRAINT chk_member_role CHECK (role IN ('admin', 'moderator', 'member')),
    UNIQUE(room_id, user_id),
    FOREIGN KEY (room_id) REFERENCES chat_rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(unique_id) ON DELETE CASCADE
);

-- Create indexes for room_members table
CREATE INDEX IF NOT EXISTS idx_room_members_room_id ON room_members(room_id);
CREATE INDEX IF NOT EXISTS idx_room_members_user_id ON room_members(user_id);
CREATE INDEX IF NOT EXISTS idx_room_members_role ON room_members(role);

-- User settings table
CREATE TABLE IF NOT EXISTS user_settings (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL,
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(user_id, setting_key),
    FOREIGN KEY (user_id) REFERENCES users(unique_id) ON DELETE CASCADE
);

-- Create indexes for user_settings table
CREATE INDEX IF NOT EXISTS idx_user_settings_user_id ON user_settings(user_id);
CREATE INDEX IF NOT EXISTS idx_user_settings_setting_key ON user_settings(setting_key);

-- Notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    data JSONB DEFAULT NULL,
    is_read BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(unique_id) ON DELETE CASCADE
);

-- Create indexes for notifications table
CREATE INDEX IF NOT EXISTS idx_notifications_user_id ON notifications(user_id);
CREATE INDEX IF NOT EXISTS idx_notifications_type ON notifications(type);
CREATE INDEX IF NOT EXISTS idx_notifications_is_read ON notifications(is_read);
CREATE INDEX IF NOT EXISTS idx_notifications_created_at ON notifications(created_at);

-- Activity logs table (for auditing)
CREATE TABLE IF NOT EXISTS activity_logs (
    id SERIAL PRIMARY KEY,
    user_id INTEGER DEFAULT NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT DEFAULT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT DEFAULT NULL,
    data JSONB DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(unique_id) ON DELETE SET NULL
);

-- Create indexes for activity_logs table
CREATE INDEX IF NOT EXISTS idx_activity_logs_user_id ON activity_logs(user_id);
CREATE INDEX IF NOT EXISTS idx_activity_logs_action ON activity_logs(action);
CREATE INDEX IF NOT EXISTS idx_activity_logs_created_at ON activity_logs(created_at);
CREATE INDEX IF NOT EXISTS idx_activity_logs_ip_address ON activity_logs(ip_address);

-- File uploads table
CREATE TABLE IF NOT EXISTS file_uploads (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INTEGER NOT NULL,
    file_type VARCHAR(100) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    upload_type VARCHAR(30) NOT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_upload_type CHECK (upload_type IN ('profile_image', 'message_attachment', 'room_image')),
    FOREIGN KEY (user_id) REFERENCES users(unique_id) ON DELETE CASCADE
);

-- Create indexes for file_uploads table
CREATE INDEX IF NOT EXISTS idx_file_uploads_user_id ON file_uploads(user_id);
CREATE INDEX IF NOT EXISTS idx_file_uploads_file_name ON file_uploads(file_name);
CREATE INDEX IF NOT EXISTS idx_file_uploads_upload_type ON file_uploads(upload_type);
CREATE INDEX IF NOT EXISTS idx_file_uploads_created_at ON file_uploads(created_at);

-- Create function to automatically update updated_at timestamp
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

-- Create triggers to automatically update updated_at columns
CREATE TRIGGER update_users_updated_at 
    BEFORE UPDATE ON users 
    FOR EACH ROW 
    EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_messages_updated_at 
    BEFORE UPDATE ON messages 
    FOR EACH ROW 
    EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_chat_rooms_updated_at 
    BEFORE UPDATE ON chat_rooms 
    FOR EACH ROW 
    EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_user_settings_updated_at 
    BEFORE UPDATE ON user_settings 
    FOR EACH ROW 
    EXECUTE FUNCTION update_updated_at_column();

-- Create function to update user last_activity when status changes to 'Active now'
CREATE OR REPLACE FUNCTION update_user_last_activity()
RETURNS TRIGGER AS $$
BEGIN
    IF NEW.status != OLD.status AND NEW.status = 'Active now' THEN
        NEW.last_activity = CURRENT_TIMESTAMP;
    END IF;
    RETURN NEW;
END;
$$ language 'plpgsql';

-- Create trigger for user activity update
CREATE TRIGGER update_user_activity_trigger
    BEFORE UPDATE ON users
    FOR EACH ROW
    EXECUTE FUNCTION update_user_last_activity();

-- Create function to log user creation
CREATE OR REPLACE FUNCTION log_user_creation()
RETURNS TRIGGER AS $$
BEGIN
    INSERT INTO activity_logs (user_id, action, description, ip_address)
    VALUES (NEW.unique_id, 'user_registered', 'New user account created', 'system');
    RETURN NEW;
END;
$$ language 'plpgsql';

-- Create trigger for user creation logging
CREATE TRIGGER log_user_creation_trigger
    AFTER INSERT ON users
    FOR EACH ROW
    EXECUTE FUNCTION log_user_creation();

-- Create function to log message read status
CREATE OR REPLACE FUNCTION log_message_read()
RETURNS TRIGGER AS $$
BEGIN
    IF NEW.is_read = TRUE AND OLD.is_read = FALSE THEN
        INSERT INTO activity_logs (user_id, action, description, ip_address)
        VALUES (NEW.incoming_msg_id, 'message_read', 'Message ' || NEW.msg_id || ' marked as read', 'system');
    END IF;
    RETURN NEW;
END;
$$ language 'plpgsql';

-- Create trigger for message read logging
CREATE TRIGGER log_message_read_trigger
    AFTER UPDATE ON messages
    FOR EACH ROW
    EXECUTE FUNCTION log_message_read();

-- Insert default admin user (password: Admin123)
INSERT INTO users (unique_id, fname, lname, email, password, img, status, is_verified) 
VALUES (
    123456789, 
    'Admin', 
    'User', 
    'admin@xdchat.com', 
    '$argon2id$v=19$m=65536,t=4,p=3$WElVY2xJQ0U5dExuSWMvUw$7d5c8e5f5a9e5e8d4c9c8e5e8e8', 
    'default-avatar.png', 
    'Active now', 
    TRUE
) ON CONFLICT (email) DO NOTHING;

-- Insert sample user settings
INSERT INTO user_settings (user_id, setting_key, setting_value) VALUES
(123456789, 'theme', 'light'),
(123456789, 'notifications', 'enabled'),
(123456789, 'sound_notifications', 'enabled'),
(123456789, 'auto_away_time', '300') -- 5 minutes
ON CONFLICT (user_id, setting_key) DO UPDATE SET setting_value = EXCLUDED.setting_value;

-- Create stored procedures (PostgreSQL functions)
CREATE OR REPLACE FUNCTION get_user_conversations(user_id_param INTEGER)
RETURNS TABLE (
    other_user_id INTEGER,
    fname VARCHAR,
    lname VARCHAR,
    img VARCHAR,
    status VARCHAR,
    last_message_time TIMESTAMP,
    last_message TEXT,
    unread_count BIGINT
) AS $$
BEGIN
    RETURN QUERY
    SELECT DISTINCT
        CASE 
            WHEN m.outgoing_msg_id = user_id_param THEN m.incoming_msg_id 
            ELSE m.outgoing_msg_id 
        END as other_user_id,
        u.fname,
        u.lname,
        u.img,
        u.status,
        MAX(m.created_at) as last_message_time,
        (SELECT msg FROM messages m2 
         WHERE (m2.outgoing_msg_id = user_id_param AND m2.incoming_msg_id = other_user_id) 
         OR (m2.outgoing_msg_id = other_user_id AND m2.incoming_msg_id = user_id_param)
         ORDER BY m2.msg_id DESC LIMIT 1) as last_message,
        (SELECT COUNT(*) FROM messages m3 
         WHERE m3.incoming_msg_id = user_id_param AND m3.outgoing_msg_id = other_user_id 
         AND m3.is_read = FALSE) as unread_count
    FROM messages m
    LEFT JOIN users u ON u.unique_id = CASE 
        WHEN m.outgoing_msg_id = user_id_param THEN m.incoming_msg_id 
        ELSE m.outgoing_msg_id 
    END
    WHERE (m.outgoing_msg_id = user_id_param OR m.incoming_msg_id = user_id_param)
    AND m.is_deleted = FALSE
    GROUP BY other_user_id, u.fname, u.lname, u.img, u.status
    ORDER BY last_message_time DESC;
END;
$$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION cleanup_old_sessions()
RETURNS VOID AS $$
BEGIN
    DELETE FROM user_sessions WHERE expires_at < CURRENT_TIMESTAMP;
    DELETE FROM login_attempts WHERE attempted_at < CURRENT_TIMESTAMP - INTERVAL '24 hours';
END;
$$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION get_online_users()
RETURNS TABLE (
    unique_id INTEGER,
    fname VARCHAR,
    lname VARCHAR,
    img VARCHAR,
    status VARCHAR,
    last_activity TIMESTAMP
) AS $$
BEGIN
    RETURN QUERY
    SELECT u.unique_id, u.fname, u.lname, u.img, u.status, u.last_activity
    FROM users u
    WHERE u.status IN ('Active now', 'Away') 
    AND u.last_activity > CURRENT_TIMESTAMP - INTERVAL '15 minutes'
    AND u.is_active = TRUE
    ORDER BY u.last_activity DESC;
END;
$$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION get_user_stats()
RETURNS TABLE (
    unique_id INTEGER,
    fname VARCHAR,
    lname VARCHAR,
    email VARCHAR,
    status VARCHAR,
    last_activity TIMESTAMP,
    messages_sent BIGINT,
    messages_received BIGINT,
    joined_date TIMESTAMP
) AS $$
BEGIN
    RETURN QUERY
    SELECT 
        u.unique_id,
        u.fname,
        u.lname,
        u.email,
        u.status,
        u.last_activity,
        COUNT(DISTINCT m_sent.msg_id) as messages_sent,
        COUNT(DISTINCT m_received.msg_id) as messages_received,
        u.created_at as joined_date
    FROM users u
    LEFT JOIN messages m_sent ON u.unique_id = m_sent.outgoing_msg_id
    LEFT JOIN messages m_received ON u.unique_id = m_received.incoming_msg_id
    WHERE u.is_active = TRUE
    GROUP BY u.unique_id;
END;
$$ LANGUAGE plpgsql;

-- Note: PostgreSQL doesn't have MySQL's EVENT SCHEDULER
-- You would need to set up a cron job or use pg_cron extension for scheduled tasks
-- Example cron job (run hourly): 0 * * * * psql -d your_database -c "SELECT cleanup_old_sessions();"