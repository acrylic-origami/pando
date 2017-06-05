<?hh // strict
namespace Pando;
type ViewFactory<-TState> = AsyncGenerator<mixed, ?\PartialViewTree<TState>, TState>