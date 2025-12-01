import React, { useState } from "react";
import ReactDOM from "react-dom/client";
import ChatSidebar from "./components/ChatSidebar.jsx";
import ChatBox from "./components/ChatBox.jsx";
import "../css/app.css";

if (document.getElementById("main")) {
  const rootUrl = "";
  const App = () => {
    const [messageCounts, setMessageCounts] = useState({});
    const [selectedChat, setSelectedChat] = useState(null);
    const [lastChat, setLastChat] = useState('');
    const userData = document.getElementById("main").getAttribute("data-user");
    const user = JSON.parse(userData);

    // Function to update message count for a chat
    const updateMessageCount = (selectedChat, newCount) => {
        setMessageCounts((prev) => ({
            [selectedChat]: newCount,
        }));
    };

    // Function to update last message for a chat
    const updateLastMessage = (selectedChat, newMessage) => {
        setLastChat((prev) => ({
            ...prev,
            [selectedChat]: newMessage,
        }));
    };

    return (
      <div className="d-flex">
        <ChatSidebar rootUrl={rootUrl} onSelectChat={setSelectedChat} messageCounts={messageCounts} lastChat={lastChat} user={user} />
        <div className="flex-grow-1"> 
          <ChatBox rootUrl={rootUrl} chatId={selectedChat} onNewMessageCount={updateMessageCount} onNewLastChat={updateLastMessage} user={user} messageCounts={messageCounts} />
        </div>
      </div>
    );
  };

  ReactDOM.createRoot(document.getElementById("main")).render(<App />);
}
