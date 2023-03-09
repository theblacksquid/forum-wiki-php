DROP DATABASE IF EXISTS fwGraph;
CREATE DATABASE fwGraph;
USE fwGraph;

--- GRAPH SCHEMA ---

DROP TABLE IF EXISTS fwGraphNodes;
CREATE TABLE fwGraphNodes
(
	nodeId		INTEGER PRIMARY KEY NOT NULL AUTO_INCREMENT,
	nodeType	VARCHAR(64) NOT NULL,
	nodeKey		VARCHAR(64) NOT NULL,
	nodeMeta	TEXT NOT NULL,

	UNIQUE INDEX(nodeType, nodeKey)
) ENGINE=INNODB;

DROP TABLE IF EXISTS fwGraphEdges;
CREATE TABLE fwGraphEdges
(
	edgeId		INTEGER PRIMARY KEY NOT NULL AUTO_INCREMENT,
	edgeType	VARCHAR(64) NOT NULL,
	edgeFrom	VARCHAR(64) NOT NULL,
	edgeTo		VARCHAR(64) NOT NULL,
	edgeData	VARCHAR(1024) NOT NULL,

	INDEX(edgeType),
	UNIQUE INDEX(edgeType, edgeFrom, edgeTo),
	INDEX(edgeType, edgeFrom),
	INDEX(edgeType, edgeTo)
) ENGINE=INNODB;

--- BUILT-IN RDF CLASSES ---

INSERT INTO fwGraphNodes (nodeType, nodeKey, nodeMeta)
VALUES
('rdfClass', 'post', ''),
('rdfClass', 'thread', ''),
('rdfClass', 'board', ''),
('rdfClass', 'postAuthor', ''),
('rdfClass', 'postDate', ''),
('rdfClass', 'postText', ''),
('rdfClass', 'postInternalLink', ''),
('rdfClass', 'threadTitle', ''),
('rdfClass', 'threadAuthor', ''),
('rdfClass', 'threadVisibility', ''),
('rdfClass', 'boardName', ''),
('rdfClass', 'boardModerator', ''),
('rdfClass', 'definitionList', ''),
('rdfClass', 'definedTerm', ''),
('rdfClass', 'definitionText', '');

INSERT INTO fwGraphEdges (edgeType, edgeFrom, edgeTo, edgeData)
SELECT 'hasField', 'post', fwGraphNodes.nodeKey, ''
FROM fwGraphNodes
WHERE fwGraphNodes.nodeKey IN
(
	'postAuthor',
	'postDate',
	'postText',
	'postInternalLink'
);

INSERT INTO fwGraphEdges (edgeType, edgeFrom, edgeTo, edgeData)
SELECT 'hasField', 'thread', fwGraphNodes.nodeKey, ''
FROM fwGraphNodes
WHERE fwGraphNodes.nodeKey IN
(
	'threadTitle',
	'threadAuthor',
	'threadVisibility'
);

INSERT INTO fwGraphEdges (edgeType, edgeFrom, edgeTo, edgeData)
SELECT 'hasField', 'board', fwGraphNodes.nodeKey, ''
FROM fwGraphNodes
WHERE fwGraphNodes.nodeKey IN
(
	'boardName',
	'boardmoderator'
);

INSERT INTO fwGraphEdges (edgeType, edgeFrom, edgeTo, edgeData)
SELECT 'hasField', 'definitionList', fwGraphNodes.nodeKey, ''
FROM fwGraphNodes
WHERE fwGraphNodes.nodeKey IN
(
	'definedTerm',
	'definitionText'
);

