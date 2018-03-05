# Pando &#x1f333;

**Pando is a server-side router and MVC library.** It is powered by a model of data flow, one where views are mapped to the database queries they used, and changes on the database are propagated to the views that depend on the changes.

Realtime apps beyond effortless under this model because they're the default. Requests that write to the persistent store are finally only responsible for that. No longer do they need to repeat their actions in a broadcast to notify the views. The complexity of notification rules is confined to the complexity of the view, like a user inbox, which renders the initial set of notifications and just consumes them over time as they're fed from the update system. (I mean, the project _began_ to make the user inbox element less of a pain to make.)

Does this sound elegant to you? I have bittersweet news.

I am overjoyed (genuinely!) to report that you can do this with ReactJS and RethinkDB. I retired this project after the realization of this slowly crept into me as I was developing Pando. I will outline below the implementation of this model in the abstract, and because I am more excited about the potential in the model than the implementation, I will show how ReactJS and RethinkDB can do this first, before the way Pando does.

## The abstract implementation

Let's think in terms of a minimal database, which just has tables, entries in these tables, and fields in these entries. I claim that when a web application has a database, it is usually most or all of the state of the application. When this is the case, the web applications that use databases can be reimagined without much distortion as maps of the database state to views.

### The start: defining views

Only two things change here.

1. Instead of passing queries to the database connection object directly, we pass them to a wrapper which stashes the query to subscribe the view to it, and then runs the query.
2. The view renderer needs to stay alive throughout the lifetime of the page to push new updates to the view. This is already the case with the DOM on the client side, but on the server side, this implies a realtime server.

<!-- >what is the official name for these? Most notably, WebSockets vs. HTTP requests -->

### The middle: data layer

First, [CRUD](https://en.wikipedia.org/wiki/Create,_read,_update_and_delete). When reasonable databases separate CRUD operations, we can also cleanly separate the queries in our appication layer between read queries (which define dependencies) and write queries (which notify dependent views). SQL does this, while conveniently dominating the database world.

Let's think about a query with the set-based language that goes naturally with relational databases. Let's say a query samples a subset of the database that intersects with the volume that the query defines.

The job of the engine is to take writes to the database and _quickly_ identify _only_ the query volumes in which the write will fall. The two italicized words, "_quickly_" and "_only_", are the trade-off that makes this problem hard, and they exist because this is a _decision problem_. In our case, the structure of queries can be much richer than the structures in the databases that are directly visible to us. We can't see the trees or hash tables from user land, only the containers: the databases, the tables, the data.

Formally, the problem here is that the space of query volumes can often be sparse, and so the entropy of any property of a given query can be very high. From a query string alone, the engine has to trade between lots of possible features to pick out from the query string (table name, WHERE clause predicates, etc. -- sparseness) and quickly cutting down the number of queries at each decision in the decision tree (entropy).

[RethinkDB famously does a fine job with this with changefeeds.](https://www.rethinkdb.com/docs/changefeeds/javascript/#changefeeds-with-filtering-and-aggregation-queries)

<sup>\*RethinkDB changefeeds scare me because I don't understand how they work.</sup>

<sup>\*\* I've splintered off [a project to find a theoretical optimum for SQL](https://www.github.com/acrylic-origami/entropy), partly out of that fear, partly out of <s>stubbornness</s> an allegiance to SQL.</sup>

### The end: updating views

After some diligent engineering and deep thinking about the transparency of our database, we find some happy medium of query analysis that maximizes the speed of identifying dependent queries. This notification turns into a re-rendering of the view renderer, which pulls in new state from the database and renders the fresh view.

## The ReactJS + RethinkDB way

Let's recap the view renderer responsibilities:

1. Propagate state through trees of dynamic views
2. Diff views after state updates and update view trees that have changed

Sounds like a job for React.

<!-- >As innocent as the tasks may look when laid down so plainly, the most time-intensive part of Pando was just designing and redesigning said tree of views around what turns out to be an infernal maze of control that weaves between it and the view renderer. ReactJS is a beautiful piece of engineering for it. -->

The idea is as follows:

1. A RethinkDB connection accessible to all components (on the client side: via [Horizon](https://github.com/rethinkdb/horizon) or some other light server-side wrapper)
2. All RethinkDB queries that are eligible for changefeeds are rewritten with an intervening `changes({ includeInitial: true })` and appropriate logic to separate the initial state from changes.
3. `setState()` where needed. Done.

(WIP, examples coming &#x1F6A7;)

## The Pando way

Pando is functionally identical. It's funny but bittersweet that I didn't know what React was before embarking on the project... ¯\\\_(ツ)\_/¯ it happens.

The way Pando works is markedly different, and it's not just the syntax. I'm fairly sure all the differences come from the language ecosystems:

1. Async-await in Hack is the reverse of event-driven async in Javascript.
2. Typescript and Flow both have far more powerful type rules for JSX than Hack does for XHP.

Beside it all, Pando also implements something akin to React Router to handle routing. The most glaring similarity is nested routes, which is a natural partner to nested views. Pando is more powerful with respect to the initial state, because async-await allows rendering the initial state to block on asynchronous operations, rather than having to render a null state that is later populated with initial data.

### The specifics (WIP &#x1F6A7;)

- Children specified by routing, passed in as props rather than returned (IoC)
- Two stages: routing and rendering. Routing prepares a view tree which is rendered and re-rendered as needed
- Push from the writes to the reads comes from IoC (the database wrapper goes into the view tree and pushes the update to the client)
- The database wrapper keeps track of which component issued which query (assuming the render tree doesn't change) in its own tree of component ids