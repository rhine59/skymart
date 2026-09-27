import SwiftUI
struct SellView:View {
 @EnvironmentObject var store:MarketplaceStore
 @State private var title="",category="Aircraft",price="",location="",details="";@State private var preview=false
 var body:some View {ScrollView{VStack(spacing:18){
  VStack(spacing:8){Image(systemName:"camera.fill").font(.largeTitle).foregroundStyle(.blue);Text("Add photos").font(.headline);Text("Great photos help aviation buyers understand your item.").font(.caption).foregroundStyle(.secondary)}.frame(maxWidth:.infinity).padding(28).background(Color.blue.opacity(.08)).clipShape(RoundedRectangle(cornerRadius:20))
  VStack(spacing:14){field("Advert title",text:$title);Picker("Category",selection:$category){ForEach(store.categories){Text($0.name).tag($0.name)}}.pickerStyle(.menu);field("Price (£)",text:$price).keyboardType(.numberPad);field("Location",text:$location);TextField("Describe condition, history and what's included",text:$details,axis:.vertical).lineLimit(5...8).padding().background(Color(.secondarySystemGroupedBackground)).clipShape(RoundedRectangle(cornerRadius:12))}
  Button{preview=true}label:{Label("Preview advert",systemImage:"eye.fill").frame(maxWidth:.infinity)}.buttonStyle(.borderedProminent).controlSize(.large).disabled(title.isEmpty || Int(price)==nil || location.isEmpty || details.isEmpty)
 }.padding()}.background(Color(.systemGroupedBackground)).navigationTitle("Sell")
 .sheet(isPresented:$preview){NavigationStack{List{Section("Preview"){ListingRow(listing:.init(id:UUID(),title:title,category:category,price:Int(price) ?? 0,location:location,details:details,seller:"You",symbol:store.categories.first{$0.name==category}?.symbol ?? "shippingbox"))}Section("Description"){Text(details)};Button("Publish prototype advert"){store.add(title:title,category:category,price:Int(price) ?? 0,location:location,details:details);title="";price="";location="";details="";preview=false}.buttonStyle(.borderedProminent)}.navigationTitle("Advert preview")}}
 }
 @ViewBuilder private func field(_ prompt:String,text:Binding<String>)->some View{TextField(prompt,text:text).textFieldStyle(.plain).padding().background(Color(.secondarySystemGroupedBackground)).clipShape(RoundedRectangle(cornerRadius:12))}
}
