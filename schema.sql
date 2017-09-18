CREATE TABLE jbrbu_job_log (
    id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    dirName VARCHAR(32) NOT NULL,
    startedStamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    endedStamp TIMESTAMP DEFAULT '2000-01-01 00:00:00', 
    what ENUM('db', 'files') NOT NULL,
    how ENUM('manual', 'scheduled') NOT NULL,
    result VARCHAR(128) DEFAULT 'executing...',
    PRIMARY KEY (id)
);