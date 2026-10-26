USE paw_patrol;

ALTER TABLE reports
  ADD COLUMN photo_data LONGBLOB NULL AFTER photo,
  ADD COLUMN photo_mime VARCHAR(40) NULL AFTER photo_data;

ALTER TABLE rescued
  ADD COLUMN photo_data LONGBLOB NULL AFTER photo,
  ADD COLUMN photo_mime VARCHAR(40) NULL AFTER photo_data;
