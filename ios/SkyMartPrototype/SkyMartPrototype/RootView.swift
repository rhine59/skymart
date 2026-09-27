import SwiftUI
struct RootView:View {
 var body:some View {TabView{
  NavigationStack{MarketplaceView()}.tabItem{Label("Browse",systemImage:"airplane")}
  NavigationStack{SellView()}.tabItem{Label("Sell",systemImage:"plus.circle.fill")}
  NavigationStack{FavouritesView()}.tabItem{Label("Saved",systemImage:"heart")}
  NavigationStack{AccountView()}.tabItem{Label("Account",systemImage:"person.crop.circle")}
 }.tint(.blue)}
}
