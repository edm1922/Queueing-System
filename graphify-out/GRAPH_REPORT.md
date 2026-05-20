# Graph Report - .  (2026-05-14)

## Corpus Check
- Corpus is ~19,082 words - fits in a single context window. You may not need a graph.

## Summary
- 70 nodes · 58 edges · 42 communities (40 shown, 2 thin omitted)
- Extraction: 100% EXTRACTED · 0% INFERRED · 0% AMBIGUOUS
- Token cost: 0 input · 0 output

## Community Hubs (Navigation)
- [[_COMMUNITY_Community 0|Community 0]]
- [[_COMMUNITY_Community 1|Community 1]]
- [[_COMMUNITY_Community 2|Community 2]]
- [[_COMMUNITY_Community 3|Community 3]]
- [[_COMMUNITY_Community 4|Community 4]]
- [[_COMMUNITY_Community 5|Community 5]]
- [[_COMMUNITY_Community 10|Community 10]]

## God Nodes (most connected - your core abstractions)
1. `refreshQueue()` - 11 edges
2. `showToast()` - 7 edges
3. `refreshStats()` - 6 edges
4. `changeWindowStatus()` - 4 edges
5. `submitNewWindow()` - 4 edges
6. `submitEditServices()` - 4 edges
7. `callCustomer()` - 4 edges
8. `completeCustomer()` - 4 edges
9. `requireAuth()` - 3 edges
10. `recallCustomer()` - 3 edges

## Surprising Connections (you probably didn't know these)
- `submitNewWindow()` --calls--> `showToast()`  [EXTRACTED]
  js/main.js → js/main.js  _Bridges community 3 → community 1_
- `submitEditServices()` --calls--> `showToast()`  [EXTRACTED]
  js/main.js → js/main.js  _Bridges community 3 → community 10_
- `completeCustomer()` --calls--> `showToast()`  [EXTRACTED]
  js/main.js → js/main.js  _Bridges community 3 → community 5_
- `changeWindowStatus()` --calls--> `refreshQueue()`  [EXTRACTED]
  js/main.js → js/main.js  _Bridges community 4 → community 3_
- `submitNewWindow()` --calls--> `refreshQueue()`  [EXTRACTED]
  js/main.js → js/main.js  _Bridges community 4 → community 1_

## Communities (42 total, 2 thin omitted)

### Community 0 - "Community 0"
Cohesion: 0.4
Nodes (3): playNotificationSound(), style, updateDisplay()

### Community 2 - "Community 2"
Cohesion: 0.6
Nodes (3): Database, requireAuth(), requireRole()

### Community 3 - "Community 3"
Cohesion: 0.5
Nodes (4): callCustomer(), changeWindowStatus(), recallCustomer(), showToast()

### Community 4 - "Community 4"
Cohesion: 0.5
Nodes (4): filterQueue(), refreshQueue(), updateCounters(), updateQueueTable()

### Community 5 - "Community 5"
Cohesion: 0.5
Nodes (4): completeCustomer(), init(), refreshStats(), updateServiceMetrics()

## Knowledge Gaps
- **1 isolated node(s):** `style`
  These have ≤1 connection - possible missing edges or undocumented components.
- **2 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `refreshQueue()` connect `Community 4` to `Community 1`, `Community 10`, `Community 3`, `Community 5`?**
  _High betweenness centrality (0.008) - this node is a cross-community bridge._
- **Why does `showToast()` connect `Community 3` to `Community 1`, `Community 10`, `Community 5`?**
  _High betweenness centrality (0.002) - this node is a cross-community bridge._
- **Why does `refreshStats()` connect `Community 5` to `Community 1`, `Community 3`?**
  _High betweenness centrality (0.002) - this node is a cross-community bridge._
- **What connects `style` to the rest of the system?**
  _1 weakly-connected nodes found - possible documentation gaps or missing edges._