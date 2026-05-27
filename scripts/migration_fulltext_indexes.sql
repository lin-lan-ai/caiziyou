-- Phase 3: Add FULLTEXT indexes for search functionality
-- Target database: caiziyou_community_db
--
-- These indexes power the /api/search endpoint, enabling fast
-- FULLTEXT searches across posts, users, and communities.
--
-- Usage:
--   mysql -u root caiziyou_community_db < scripts/migration_fulltext_indexes.sql
--   or via the wrapper:
--   bash scripts/run_fulltext_migration.sh

USE caiziyou_community_db;

-- Posts fulltext search (title + description)
ALTER TABLE community_posts
  ADD FULLTEXT INDEX ft_posts_search (title, description);

-- Users fulltext search (username + nickname)
ALTER TABLE users
  ADD FULLTEXT INDEX ft_users_search (username, nickname);

-- Communities fulltext search (name + description)
ALTER TABLE communities
  ADD FULLTEXT INDEX ft_communities_search (name, description);
