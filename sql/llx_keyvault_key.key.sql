-- Copyright (C) 2025		François Brichart			<francois@disqutons.fr>
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program.  If not, see https://www.gnu.org/licenses/.


-- BEGIN MODULEBUILDER INDEXES
ALTER TABLE llx_keyvault_key ADD INDEX idx_keyvault_key_rowid (rowid);
ALTER TABLE llx_keyvault_key ADD UNIQUE INDEX uk_keyvault_key_ref (ref);
ALTER TABLE llx_keyvault_key ADD INDEX idx_keyvault_key_fk_soc (fk_soc);
ALTER TABLE llx_keyvault_key ADD INDEX idx_keyvault_key_rights_user (rights_user);
ALTER TABLE llx_keyvault_key ADD INDEX idx_keyvault_key_rights_group (rights_group);
ALTER TABLE llx_keyvault_key ADD INDEX idx_keyvault_key_status (status);
-- END MODULEBUILDER INDEXES

--ALTER TABLE llx_keyvault_key ADD UNIQUE INDEX uk_keyvault_key_fieldxy(fieldx, fieldy);

--ALTER TABLE llx_keyvault_key ADD CONSTRAINT llx_keyvault_key_fk_field FOREIGN KEY (fk_field) REFERENCES llx_keyvault_myotherobject(rowid);
